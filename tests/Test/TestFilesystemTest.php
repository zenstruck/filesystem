<?php

namespace Zenstruck\Filesystem\Tests\Test;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\Test\TestFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestFilesystemTest extends FilesystemTest
{
    protected function createFilesystem(): Filesystem
    {
        return new TestFilesystem(new FlysystemFilesystem(self::TEMP_DIR));
    }
}
