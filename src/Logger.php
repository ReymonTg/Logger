<?php declare(strict_types=1);

/**
 * This file is part of Reymon.
 * Reymon is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Reymon is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    AhJ <AmirHosseinJafari8228@gmail.com>
 * @copyright 2023-2024 AhJ <AmirHosseinJafari8228@gmail.com>
 * @license   https://choosealicense.com/licenses/gpl-3.0/ GPLv3
 */

namespace Reymon\Logger;

use Throwable;
use Stringable;
use DateTimeZone;
use DateTimeImmutable;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;
use Psr\Log\LoggerInterface;
use Psr\Log\InvalidArgumentException;
use Amp\File\File;
use Amp\Sync\Lock;
use Amp\Sync\Mutex;
use Amp\DeferredFuture;
use Amp\SignalException;
use Amp\Sync\LocalMutex;
use Amp\ByteStream\WritableResourceStream;
use const E_ALL;
use const SIGINT;
use const SIG_DFL;
use const SIGTERM;
use const PHP_SAPI;
use const DIRECTORY_SEPARATOR;
use function Amp\ByteStream\getOutputBufferStream;

class Logger implements LoggerInterface
{
    /** Whether to suspend certain stdout log printing, when reading input. */
    protected ?DeferredFuture $suspendPeriodicLogging = null;
    protected DateTimeZone $timezone;
    protected bool $isatty;
    protected string $prefix = '';
    protected string $suffix = '';
    protected string $dateFormat = 'Y-m-d H:i:s';
    protected Mutex $mutex;
    protected ?Lock $lock;

    protected bool $fullName = true;

    public function __construct(protected WritableResourceStream|File $stream, ?DateTimeZone $timezone = null)
    {
        $this->isatty = \defined('STDOUT') && LogLevel::hasColorSupport();
        $this->setTimezone($timezone);
        // Setup error reporting
        \set_error_handler($this->exceptionErrorHandler(...));
        \set_exception_handler($this->exceptionHandler(...));
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            try {
                \error_reporting(E_ALL);
                \ini_set('log_errors', 1);
                \ini_set(
                    'error_log',
                    $this->stream instanceof File
                        ? $this->stream->getPath()
                        : $this->getCwd() . DIRECTORY_SEPARATOR . 'Reymon.log'
                );
            } catch (Throwable $e) {
                error_log("Could not enable PHP logging $e");
            }
        }

        try {
            \ini_set('memory_limit', -1);
        } catch (Throwable $e) {
        }

        try {
            if (\function_exists('set_time_limit')) {
                \set_time_limit(-1);
            }
        } catch (Throwable $e) {
        }

        // Define signal handlers
        if (\defined('SIGINT')) {
            try {
                pcntl_signal(SIGINT, static fn () => null);
                pcntl_signal(SIGINT, SIG_DFL);
                EventLoop::unreference(EventLoop::onSignal(SIGINT, function (): void {
                    if ($this->suspendPeriodicLogging) {
                        $this->togglePeriodicLogging();
                    }
                    throw new SignalException('SIGINT received');
                }));
                EventLoop::unreference(EventLoop::onSignal(SIGTERM, function (): void {
                    if ($this->suspendPeriodicLogging) {
                        $this->togglePeriodicLogging();
                    }
                    throw new SignalException('SIGTERM received');
                }));
            } catch (Throwable $e) {
            }
        }
        $this->mutex = new LocalMutex;
        $this->lock = null;
    }

    private function getCwd(): string
    {
        try {
            return getcwd();
        } catch (Throwable) {
            $backtrace = debug_backtrace(0);
            return \dirname(end($backtrace)['file']);
        }
    }

    /**
     * @internal
     *
     * Error handler
     */
    public function exceptionErrorHandler($errno = 0, $errstr = null, $errfile = null, $errline = null): bool
    {
        // if (!$this->stream->isClosed()) {
            $level = match ($errno) {
                E_ERROR  , E_USER_ERROR   => LogLevel::ERROR,
                E_WARNING, E_USER_WARNING => LogLevel::WARNING,
                E_NOTICE , E_USER_NOTICE  => LogLevel::NOTICE,
                default => LogLevel::CRITICAL
            };
            $this->log($level, $errstr . ' in ' . \basename($errfile) . ':' . $errline);
        // }
        return true;
    }

    /**
     * @internal
     *
     * ExceptionErrorHandler.
     */
    public function exceptionHandler(Throwable $exception): void
    {
        // if (!$this->stream->isClosed())
            $this->critical($exception);
        $this->echoException($exception);
    }

    public function echoException(Throwable $exception): void
    {
        $e = (string) $exception;
        if (\headers_sent())
            return;

        \http_response_code(500);
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            $message = $e . PHP_EOL;
        } else {
            $message = \nl2br(\htmlentities($e)) . PHP_EOL;
        }
        getOutputBufferStream()->write($message);
    }

    public function __destruct()
    {
        if (!$this->stream->isClosed())
            $this->stream->end();
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    private function interpolate(string $message, array $context = []): string
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param LogLevel          $level
     * @param string|Stringable $message
     * @param array             $context
     *
     * @throws InvalidArgumentException
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if ($this->suspendPeriodicLogging) {
            $this->suspendPeriodicLogging->getFuture()->map(fn () => $this->log($level, $message, $context));
            return;
        }
        $lock = $this->mutex->acquire();
        try {
            Assert::isInstanceOf($level, LogLevel::class);
            if ($message instanceof Throwable) {
                $file = $message->getFile();
            } else {
                $d    = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                $file = end($d)['file'] ?? 'Not.php';
            }
            $message = (string) $message;
            $message = $this->interpolate($message, $context);
            $format  = $this->format($level, $message, $file);
            $this->stream->write($format);
        } finally {
            $lock->release();
        }
    }

    private function format(LogLevel $level, string $message, string $file): string
    {
        $file = $this->fullName ? $file : basename($file, '.php');
        $time = (new DateTimeImmutable(timezone: $this->timezone))->format($this->dateFormat);
        $info = $this->prefix . "[$time] " . $level->getBracket() . " [$file]" . $this->suffix . ':';
        if (!$this->stream instanceof File && $this->isatty)
            $info = $level->getCliColor($info);
        return $info . ' ' . $message . PHP_EOL;
    }

//    public function formatFile(LogLevel $level, string $message, $file): string
//    {
//        $info = $this->prefix . "[{$this->getTime()}] " . $level->getBracket() . " [$file]:" . $this->suffix;
//        if (!\headers_sent() && PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
//            \http_response_code(500);
//            getOutputBufferStream()->write(sprintf($level->getWebColor(), $info) . ' ' . htmlentities($message) . '<br>');
//        }
//        return $info . ' ' . $message . PHP_EOL;
//    }

    /**
     * Toggle periodic logging.
     */
    public function togglePeriodicLogging(): void
    {
        if ($this->suspendPeriodicLogging) {
            $deferred = $this->suspendPeriodicLogging;
            $this->suspendPeriodicLogging = null;
            $deferred->complete();
        } else {
            $this->suspendPeriodicLogging = new DeferredFuture;
            $f = new DeferredFuture;
            $f->complete();
            $f->getFuture()->await();
        }
    }

    /**
     * Set time format
     *
     * @param string $format
     */
    public function setDateFormat(string $format): self
    {
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * Get time format
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Set suffix to be used for the log records
     *
     * @param string $suffix The suffix
     */
    public function setSuffix(string $suffix): self
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * Get suffix to be used for the log records
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Set prefix to be used for the log records
     *
     * @param string $prefix The prefix
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Get prefix to be used for log records
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Sets the timezone to be used for the timestamp of log records.
     *
     * @param ?DateTimeZone $tz Time zone
     */
    public function setTimezone(?DateTimeZone $tz = null): self
    {
        $this->timezone = $tz ?? new DateTimeZone(date_default_timezone_get());
        return $this;
    }

    /**
     * Returns the timezone to be used for the timestamp of log records.
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * Whether to show full file name
     *
     * @param bool $fullName
     */
    public function setFullName(bool $fullName = false): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    /**
     * Whether to show full file name
     */
    public function getFullName(): bool
    {
        return $this->fullName;
    }

    /**
     * System is unusable.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string|Stringable $message
     * @param array             $context
     *
     * @return void
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
