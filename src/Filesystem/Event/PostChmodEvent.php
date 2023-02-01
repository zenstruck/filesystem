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

use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends PostOperationEvent<PreChmodEvent>
 */
final class PostChmodEvent extends PostOperationEvent
{
    public function node(): Node
    {
        return $this->filesystem()->node($this->path());
    }

    public function path(): string
    {
        return $this->event->path;
    }

    public function visibility(): string
    {
        return $this->event->visibility;
    }
}
