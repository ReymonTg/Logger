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

enum Colors: string
{
    const BLACK             = "\033[0;30m";
    const DARK_GRAY         = "\033[1;30m";
    const RED               = "\033[0;31m";
    const LIGHT_RED         = "\033[1;31m";
    const GREEN             = "\033[0;32m";
    const LIGHT_GREEN       = "\033[1;32m";
    const YELLOW            = "\033[0;33m";
    const BLUE              = "\033[0;34m";
    const LIGHT_BLUE        = "\033[1;34m";
    const MAGENTA           = "\033[0;35m";
    const PURPLE            = "\033[2;35m";
    const LIGHT_MAGENTA     = "\033[1;35m";
    const CYAN              = "\033[0;36m";
    const LIGHT_CYAN        = "\033[1;36m";
    const LIGHT_GRAY        = "\033[2;37m";
    const BOLD_WHITE        = "\033[1;38m";
    const WHITE             = "\033[0;38m";
    const FG_DEFAULT        = "\033[39m";
    const GRAY              = "\033[0;90m";
    const LIGHT_RED_ALT     = "\033[91m";
    const LIGHT_GREEN_ALT   = "\033[92m";
    const LIGHT_YELLOW_ALT  = "\033[93m";
    const LIGHT_YELLOW      = "\033[1;93m";
    const LIGHT_BLUE_ALT    = "\033[94m";
    const LIGHT_MAGENTA_ALT = "\033[95m";
    const LIGHT_CYAN_ALT    = "\033[96m";
    const LIGHT_WHITE_ALT   = "\033[97m";
}
