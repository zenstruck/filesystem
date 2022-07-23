<?php

namespace Zenstruck\Filesystem\Tests\Adapter;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InMemoryAdapterTest extends FilesystemTest
{
    protected function createFilesystem(): Filesystem
    {
        return new AdapterFilesystem(new InMemoryFilesystemAdapter());
    }
}
