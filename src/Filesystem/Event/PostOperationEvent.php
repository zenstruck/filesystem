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

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of PreOperationEvent
 */
abstract class PostOperationEvent extends Filesystem\Event\OperationEvent
{
    /**
     * @internal
     *
     * @param Filesystem\Event\T $event
     */
    public function __construct(protected Filesystem\Event\PreOperationEvent $event)
    {
    }

    final public function filesystem(): Filesystem
    {
        return $this->event->filesystem();
    }
}
