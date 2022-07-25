<?php

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyFilesystem implements Filesystem
{
    use WrappedFilesystem;

    /** @var Filesystem|callable():Filesystem */
    private $inner;

    /**
     * @param callable():Filesystem $factory
     */
    public function __construct(callable $factory)
    {
        $this->inner = $factory;
    }

    protected function inner(): Filesystem
    {
        if ($this->inner instanceof Filesystem) {
            return $this->inner;
        }

        return $this->inner = ($this->inner)();
    }
}
