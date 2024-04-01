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

namespace Reymon\Logger\Design\Web;

enum Colors: string
{
    const DEFAULT       = '';
    const BLACK         = '<font color="black">%s</font>';
    const DARK_GRAY     = '<font color="#a9a9a9">%s</font>';
    const RED           = '<font color="red">%s</font>';
    const LIGHT_RED     = '<font color="#FFCCCB">%s</font>';
    const GREEN         = '<font color="green">%s</font>';
    const YELLOW        = '<font color="yellow">%s</font>';
    const MAGENTA       = '<font color="#ff00ff">%s</font>';
    const PURPLE        = '<font color="purple">%s</font>';
    const LIGHT_MAGENTA = '<font color=" #ff80ff">%s</font>';
    const LIGHT_GREEN   = '<font color="#90ee90">%s</font>';
    const CYAN          = '<font color="#00ffff">%s</font>';
    const LIGHT_CYAN    = '<font color="#e0ffff">%s</font>';
    const LIGHT_GRAY    = '<font color="#d3d3d3">%s</font>';
    const LIGHT_YELLOW  = '<font color="#ffffe0">%s</font>';
    const WHITE         = '<font color="white">%s</font>';
    const GRAY          = '<font color="gray">%s</font>';
    const BLUE          = '<font color="blue">%s</font>';
}
