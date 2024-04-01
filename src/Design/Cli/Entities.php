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

enum Entities
{
    const DEFAULT       = '';
    const REST          = "\e[0m";
    const BOLD          = "\e[1m";
    const UN_BOLD       = "\e[21m";
    const DIM           = "\e[2m";
    const UN_DIM        = "\e[22m";
    const UNDERLINED    = "\e[4m";
    const UN_UNDERLINED = "\e[24m";
    const BLINK         = "\e[5m";
    const UN_BLINK      = "\e[25m";
    const HIGHLIGHT     = "\e[7m";
    const UN_HIGHLIGHT  = "\e[27m";
    const HIDDEN        = "\e[8m";
    const UN_HIDDEN     = "\e[28m";
}
