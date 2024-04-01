<?php declare(strict_types=1);

/**
 * This file is part of Reymon.
 * Reymon is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * Reymon is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    AhJ <AmirHosseinJafari8228@gmail.com>
 * @author    Mahdi <mahdi.talaee1379@gmail.com>
 * @copyright 2023-2024 Mahdi <mahdi.talaee1379@gmail.com>
 * @license   https://choosealicense.com/licenses/gpl-3.0/ GPLv3
 */

namespace Reymon\Logger\Design\Cli;

enum BackgroundColors: string
{
    const BLACK         = "\033[40m";
    const RED           = "\033[41m";
    const GREEN         = "\033[42m";
    const YELLOW        = "\033[43m";
    const BLUE          = "\033[44m";
    const MAGENTA       = "\033[45m";
    const CYAN          = "\033[46m";
    const LIGHT_GRAY    = "\033[47m";
    const DEFAULT       = "\033[49m";
    const DARK_GRAY     = "\e[100m";
    const LIGHT_RED     = "\e[101m";
    const LIGHT_GREEN   = "\e[102m";
    const LIGHT_YELLOW  = "\e[103m";
    const LIGHT_BLUE    = "\e[104m";
    const LIGHT_MAGENTA = "\e[105m";
    const LIGHT_CYAN    = "\e[106m";
    const WHITE         = "\e[107m";
}
