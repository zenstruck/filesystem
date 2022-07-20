<?php

namespace Zenstruck\Filesystem\Tests\Flysystem\Adapter;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InMemoryAdapterTest extends FilesystemTestCase
{
    protected function createFilesystem(): Filesystem
    {
        return new FlysystemFilesystem(new InMemoryFilesystemAdapter());
    }
}
