<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Event;

use Zenstruck\Filesystem\Node\Directory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends PostOperationEvent<PreMkdirEvent>
 */
final class PostMkdirEvent extends PostOperationEvent
{
    public function directory(): Directory
    {
        return $this->filesystem()->directory($this->path());
    }

    public function path(): string
    {
        return $this->event->path;
    }

    public function content(): \SplFileInfo|Directory|null
    {
        return $this->event->content;
    }

    public function config(): array
    {
        return $this->event->config;
    }
}
