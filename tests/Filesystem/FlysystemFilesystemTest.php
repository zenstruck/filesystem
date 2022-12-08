<?php

namespace Zenstruck\Tests\Filesystem;

use Zenstruck\Filesystem;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFilesystemTest extends FilesystemTest
{
    protected function createFilesystem(): Filesystem
    {
        return in_memory_filesystem();
    }
}
