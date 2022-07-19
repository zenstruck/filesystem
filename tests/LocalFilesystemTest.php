<?php

namespace Zenstruck\Filesystem\Tests;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LocalFilesystemTest extends FilesystemTestCase
{
    protected function createFilesystem(): Filesystem
    {
        return new FlysystemFilesystem(self::TEMP_DIR);
    }
}
