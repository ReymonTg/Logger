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

use Reymon\Logger\Design\Cli\BackgroundColors;
use Reymon\Logger\Design\Cli\Colors as CliColors;
use Reymon\Logger\Design\Cli\Entities;
use Reymon\Logger\Design\Web\Colors as WebColors;

enum LogLevel: string
{
    case EMERGENCY = 'emergency';
    case ALERT     = 'alert';
    case CRITICAL  = 'critical';
    case ERROR     = 'error';
    case WARNING   = 'warning';
    case NOTICE    = 'notice';
    case INFO      = 'info';
    case DEBUG     = 'debug';

    public function getBracket(): string
    {
        return "[" . $this->name . "]" . \str_pad('', 9 - \mb_strlen($this->name));
    }

    public function getCliColor(string $message): string
    {
        $color = match ($this) {
            LogLevel::DEBUG     => CliColors::BOLD_WHITE,
            LogLevel::INFO      => CliColors::GREEN,
            LogLevel::NOTICE    => CliColors::BLUE,
            LogLevel::WARNING   => CliColors::YELLOW,
            LogLevel::ERROR     => CliColors::MAGENTA,
            LogLevel::CRITICAL  => CliColors::RED,
            LogLevel::ALERT     => CliColors::RED . Entities::UNDERLINED,
            LogLevel::EMERGENCY => CliColors::RED . Entities::HIGHLIGHT
        };
        return $color . BackgroundColors::BLACK . $message . Entities::REST;
    }

    public function getWebColor(string $message): string
    {
        $color = match ($this) {
            LogLevel::DEBUG     => WebColors::GREEN,
            LogLevel::INFO      => WebColors::BLUE,
            LogLevel::NOTICE    => WebColors::DARK_GRAY,
            LogLevel::WARNING   => WebColors::YELLOW,
            LogLevel::ERROR     => WebColors::LIGHT_RED,
            LogLevel::CRITICAL  => WebColors::RED,
            LogLevel::ALERT     => WebColors::CYAN,
            LogLevel::EMERGENCY => WebColors::LIGHT_MAGENTA,
        };
        return \sprintf($color, $message);
    }

    /**
     * @see     https://github.com/amphp/log/blob/2.x/src/functions.php#L5
     * @license https://github.com/amphp/log/blob/2.x/LICENSE
     */
    public static function hasColorSupport(): bool
    {
        static $supported;

        if ($supported !== null) {
            return $supported;
        }

        \set_error_handler(static fn () => true);

        try {
            // @see https://github.com/symfony/symfony/blob/v4.0.6/src/Symfony/Component/Console/Output/StreamOutput.php#L91
            // @license https://github.com/symfony/symfony/blob/v4.0.6/LICENSE
            if (\PHP_OS_FAMILY === 'Windows') {
                /** @psalm-suppress UndefinedConstant */
                $windowsVersion = \sprintf(
                    '%d.%d.%d',
                    \PHP_WINDOWS_VERSION_MAJOR,
                    \PHP_WINDOWS_VERSION_MINOR,
                    \PHP_WINDOWS_VERSION_BUILD,
                );

                return $supported = (\function_exists('sapi_windows_vt100_support') && \sapi_windows_vt100_support(\STDOUT))
                    || $windowsVersion === '10.0.10586' // equals is correct here, newer versions use the above function
                    || false !== \getenv('ANSICON')
                    || 'ON' === \getenv('ConEmuANSI')
                    || 'xterm' === \getenv('TERM');
            }

            if (\function_exists('posix_isatty')) {
                return $supported = posix_isatty(\STDOUT);
            }
        } finally {
            \restore_error_handler();
        }
        return $supported = false;
    }
}
