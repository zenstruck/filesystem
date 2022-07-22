<?php

namespace Zenstruck\Filesystem\Tests\Flysystem\Adapter;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LocalAdapterTest extends FilesystemTest
{
    protected function createFilesystem(): Filesystem
    {
        return new FlysystemFilesystem(self::TEMP_DIR);
    }
}
