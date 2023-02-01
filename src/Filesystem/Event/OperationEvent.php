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

use Psr\EventDispatcher\StoppableEventInterface;
use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class OperationEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    abstract public function filesystem(): Filesystem;

    final public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    final public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
