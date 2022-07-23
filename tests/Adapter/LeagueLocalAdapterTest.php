<?php

namespace Zenstruck\Filesystem\Tests\Adapter;

use League\Flysystem\Local\LocalFilesystemAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LeagueLocalAdapterTest extends FilesystemTest
{
    protected function createFilesystem(): Filesystem
    {
        return new AdapterFilesystem(new LocalFilesystemAdapter(self::TEMP_DIR));
    }
}
