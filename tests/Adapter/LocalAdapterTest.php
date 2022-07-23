<?php

namespace Zenstruck\Filesystem\Tests\Adapter;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LocalAdapterTest extends FilesystemTest
{
    protected function createFilesystem(): Filesystem
    {
        return new AdapterFilesystem(self::TEMP_DIR);
    }
}
