<?php

namespace Zenstruck\Filesystem\Tests;

use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFilesystemTest extends FilesystemTestCase
{
    protected function createFilesystem(): Filesystem
    {
        return new FlysystemFilesystem(new Flysystem(new LocalFilesystemAdapter(self::TEMP_DIR)));
    }
}
