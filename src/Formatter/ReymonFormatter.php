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

namespace Reymon\Logger\Formatter;

use Reymon\Logger\Record;
use Reymon\Logger\Formatter;
use Monolog\Handler\WebRequestRecognizerTrait;

final class ReymonFormatter implements Formatter
{
    use WebRequestRecognizerTrait;

    public function __construct(?string $format = null, protected ?string $dateFormat = null, protected bool $allowInlineLineBreaks = false, protected bool $ignoreEmptyContextAndExtra = true, protected bool $includeStacktraces = false)
    {
        $this->dateFormat ??= 'Y-m-d\TH:i:sP';
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function format(Record $record): mixed
    {
        return '';
    }
}
