<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem;

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
        $filesystem = new FlysystemFilesystem(new InMemoryFilesystemAdapter());

        $this->assertSame('default', $filesystem->name());

        $filesystem = new FlysystemFilesystem(new InMemoryFilesystemAdapter(), 'public');

        $this->assertSame('public', $filesystem->name());
    }

    /**
     * @test
     */
    public function node_has_dsn(): void
    {
        $filesystem = new FlysystemFilesystem(new InMemoryFilesystemAdapter(), 'public');
        $node = $filesystem->write('file.txt', 'content')->last();

        $this->assertSame('public://file.txt', (string) $node->dsn());
        $this->assertSame('public', $node->dsn()->filesystem());
        $this->assertSame('file.txt', $node->dsn()->path()->toString());
    }

    protected function createFilesystem(): Filesystem
    {
        return in_memory_filesystem();
    }
}
