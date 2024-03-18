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

use Reymon\Logger\Formatter\ReymonFormatter;
use Throwable;
use DateTimeZone;
use DateTimeImmutable;
use Amp\File\File;
use Amp\ByteStream\WritableStream;
use Exception;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    use LoggerTrait;

    protected DateTimeZone $timezone;

    public function __construct(protected WritableStream $stream, ?DateTimeZone $timezone = null, protected Formatter $formatter = new ReymonFormatter())
    {
        $this->setTimezone($timezone);
        // Setup error reporting
        \set_error_handler($this->exceptionErrorHandler(...));
        \set_exception_handler($this->exceptionHandler(...));
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            try {
                \error_reporting(E_ALL);
                \ini_set('log_errors', 1);
                \ini_set('error_log', $this->stream instanceof File
                    ? $this->stream->getPath()
                    : $this->getCwd() . DIRECTORY_SEPARATOR . 'Reymon.log'
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
    }

    private function getCwd(): string
    {
        try {
            return getcwd();
        } catch (Throwable $e) {
            $backtrace = debug_backtrace(0);
            return \dirname(end($backtrace)['file']);
        }
    }
    public function setFormatter(Formatter $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    public function getFormatter(): Formatter
    {
        return $this->formatter;
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
        // Assert::isInstanceOf($level, LogLevel::class);

        if ($message instanceof Exception) {
            $message = (string) $message;
        } elseif (!\is_string($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }
        if (empty($file)) {
            $file = basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'], '.php');
        }
        $record = new Record($this->timezone, $level, $message . PHP_EOL, $context);
        $format = $this->formatter->format($record);
        $this->stream->write($format);
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

    public function __destruct()
    {
        $this->stream->end();
    }
}
