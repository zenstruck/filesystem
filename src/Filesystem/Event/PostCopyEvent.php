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
 * @extends PostOperationEvent<PreCopyEvent>
 */
final class PostCopyEvent extends PostOperationEvent
{
    public function sourceNode(): Node
    {
        return $this->filesystem()->node($this->source());
    }

    public function destinationNode(): Node
    {
        return $this->filesystem()->node($this->destination());
    }

    public function source(): string
    {
        return $this->event->source;
    }

    public function destination(): string
    {
        return $this->event->destination;
    }

    public function config(): array
    {
        return $this->event->config;
    }
}
