<?php

namespace Zenstruck\Filesystem\Tests\Flysystem\Adapter;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LocalAdapterTest extends FilesystemTestCase
{
    protected function createFilesystem(): Filesystem
    {
        return new FlysystemFilesystem(self::TEMP_DIR);
    }
}
