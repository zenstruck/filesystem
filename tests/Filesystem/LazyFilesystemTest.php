<?php

namespace Zenstruck\Tests\Filesystem;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\LazyFilesystem;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyFilesystemTest extends FilesystemTest
{
    protected function createFilesystem(): Filesystem
    {
        return new LazyFilesystem(fn() => in_memory_filesystem());
    }
}
