<?php

namespace Zenstruck\Tests\Filesystem;

use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFilesystemTest extends FilesystemTest
{
    /**
     * @test
     */
    public function can_set_a_name(): void
    {
        $filesystem = new FlysystemFilesystem(new Flysystem(new InMemoryFilesystemAdapter()));

        $this->assertSame('default', $filesystem->name());

        $filesystem = new FlysystemFilesystem(new Flysystem(new InMemoryFilesystemAdapter()), 'public');

        $this->assertSame('public', $filesystem->name());
    }

    protected function createFilesystem(): Filesystem
    {
        return in_memory_filesystem();
    }
}