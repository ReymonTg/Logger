<?php declare(strict_types=1);

/**
 * This file is part of Reymon.
 * Reymon is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Reymon is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    AhJ <AmirHosseinJafari8228@gmail.com>
 * @copyright 2023-2023 AhJ <AmirHosseinJafari8228@gmail.com>
 * @license   https://choosealicense.com/licenses/gpl-3.0/ GPLv3
 */

namespace Reymon\Logger;

use DateTime;
use Throwable;
use DateTimeZone;
use Reymon\Magic;
use Amp\File\File;
use Reymon\Shutdown;
use Psr\Log\LogLevel;
use Revolt\EventLoop;
use Reymon\Exception;
use Amp\Log\StreamHandler;
use Psr\Log\AbstractLogger;
use function Amp\File\exists;

use function Amp\File\getSize;
use function Amp\File\openFile;
use Amp\ByteStream\WritableStream;
use Reymon\Settings\Logger\LogType;

use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use Amp\ByteStream\WritableResourceStream;
use Reymon\Settings\Logger as SettingsLogger;

class Logger extends AbstractLogger
{
    private string $loggerLoop = '';
    private DateTimeZone $timezone;
    private WritableStream|File $stream;

    public function __construct(SettingsLogger $logSetting, ?DateTimeZone $timezone = null)
    {
        $this->setTimezone($timezone ?? new DateTimeZone(date_default_timezone_get()));
        $this->setLogStream($logSetting);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string|\Stringable $message
     * @param array  $context
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        // if ($this->mode === self::CALLABLE_LOGGER) {
        //     EventLoop::queue($this->optional, $param, $level);
        //     return;
        // }
        if ($message instanceof Throwable) {
            $message = (string) $message;
        } elseif (!\is_string($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        if (empty($file)) {
            $file = basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        $param = str_pad($file . ': ', 16) . "\t" . $message . PHP_EOL;
        $this->stream->write($param);
    }

    /**
     * @internal
     *
     * Error handler
     */
    public function exceptionErrorHandler($errno = 0, $errstr = null, $errfile = null, $errline = null): bool
    {
        $this->critical($errstr . ' in ' . \basename($errfile) . ':' . $errline);
        return true;
    }

    /**
     * @internal
     *
     * ExceptionErrorHandler.
     */
    public function exceptionHandler(Throwable $exception): void
    {
        $e = (string) $exception;
        $this->critical($e);
        if (\headers_sent())
            return;

        \http_response_code(500);
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            echo($e . PHP_EOL);
        } else {
            echo(\str_replace("\n", "<br>", \htmlentities($e)).PHP_EOL);
        }
        die(1);
    }

    /**
     * Sets the timezone to be used for the timestamp of log records.
     *
     * @return $this
     */
    public function setTimezone(DateTimeZone $tz): self
    {
        $this->timezone = $tz;
        return $this;
    }

    /**
     * Returns the timezone to be used for the timestamp of log records.
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    private function setLogStream(SettingsLogger $logSetting)
    {
        $path = $logSetting->getPath() ?? Magic::getcwd() . DIRECTORY_SEPARATOR . '/Reymon.log';
        $type = $logSetting->getType();
        $max  = $logSetting->getMaxSize();
        // Setup error reporting
        Shutdown::init();
        \set_error_handler($this->exceptionErrorHandler(...));
        \set_exception_handler($this->exceptionHandler(...));
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            try {
                \error_reporting(E_ALL);
                \ini_set('log_errors', 1);
                \ini_set('error_log', $type === LogType::FILE
                    ? $path
                    : Magic::$scriptCwd . DIRECTORY_SEPARATOR . 'Reymon.log'
                );
            } catch (Throwable $e) {
                error_log("Could not enable PHP logging $e");
            }
        }

        try {
            \ini_set('memory_limit', -1);
        } catch (Throwable $e) {}

        try {
            if (\function_exists('set_time_limit')) {
                \set_time_limit(-1);
            }
        } catch (Throwable $e) {}

        switch ($type) 
        {
            case LogType::FILE:
                $stdout = openFile($path, 'w+');
                if ($max !== -1) {
                    $this->loggerLoop = EventLoop::repeat(
                        10,
                        function () use ($max, $path, &$stdout): void {
                            \clearstatcache(true, $path);
                            if (exists($path) && (getSize($path) >= $max)) {
                                $stdout->truncate(0);
                                $this->notice("Automatically truncated logfile to $max, Reymon");
                            }
                        },
                    );
                    // EventLoop::unreference($loggerLoop);
                }
                break;
            case LogType::ECHO:
                $stdout = getStdout();
                break;
            case LogType::DEFAULT_LOGGER:
                $result = @\ini_get('error_log');
                $stdout = match ($result) {
                    false, 'syslog' => getStderr(),
                    default => openFile($result, 'a+')
                };
                break;
        }
        $this->stream = $stdout;
    }

    /**
     * Truncate log.
     */
    public function truncate(): void
    {
        $this->stream instanceof File
            ? $this->stream->truncate(0)
            : $this->stream->write("\033[2J\033[;H");
    }

    public function __destruct()
    {
        if (!empty($this->loggerLoop))
            EventLoop::cancel($this->loggerLoop);
    }
}
