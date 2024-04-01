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

namespace Reymon\Logger\Logger;

use DateTimeZone;
use Revolt\EventLoop;
use Reymon\Logger\Logger;
use function Amp\File\exists;
use function Amp\File\getSize;
use function Amp\File\openFile;

final class FileLogger extends Logger
{
    private string $loggerLoop = '';

    public function __construct($path, $maxSize = -1, ?DateTimeZone $timezone = null)
    {
        $file = openFile($path, 'w+');
        parent::__construct($file, $timezone);
        if ($maxSize !== -1) {
            $this->loggerLoop = EventLoop::repeat(
                10,
                function () use ($maxSize, $path): void {
                    \clearstatcache(true, $path);
                    if (exists($path) && (getSize($path) >= $maxSize)) {
                        $this->truncate();
                        $this->notice("Automatically truncated logfile to $maxSize, Reymon");
                    }
                },
            );
            // EventLoop::unreference($loggerLoop);
        }
    }

    /**
     * Truncate log.
     */
    public function truncate(): void
    {
        $this->stream->truncate(0);
    }
    
    public function __destruct()
    {
        if (!empty($this->loggerLoop))
            EventLoop::cancel($this->loggerLoop);
        parent::__destruct();
    }
}
