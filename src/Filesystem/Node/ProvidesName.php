<?php

namespace Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait ProvidesName
{
    public function name(): string
    {
        return \pathinfo($this->path(), \PATHINFO_BASENAME);
    }

    abstract public function path(): string;
}
