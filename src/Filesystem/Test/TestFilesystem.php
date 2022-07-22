<?php

namespace Zenstruck\Filesystem\Test;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\WrappedFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestFilesystem extends WrappedFilesystem
{
    public function __construct(private Filesystem $inner)
    {
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }
}
