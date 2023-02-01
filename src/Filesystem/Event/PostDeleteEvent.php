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
 * @extends PostOperationEvent<PreDeleteEvent>
 */
final class PostDeleteEvent extends PostOperationEvent
{
    public function path(): Directory|string
    {
        return $this->event->path;
    }

    public function config(): array
    {
        return $this->event->config;
    }
}
