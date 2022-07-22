<?php

namespace Zenstruck\Filesystem\Tests;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\LazyFilesystem;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyFilesystemTest extends FilesystemTest
{
    use InteractsWithFilesystem;

    protected function createFilesystem(): Filesystem
    {
        return new LazyFilesystem(fn() => $this->filesystem());
    }
}
