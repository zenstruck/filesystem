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

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class MultiFilesystemTest extends FilesystemTest
{
    /**
     * @test
     */
    public function can_nest_multi_filesystems(): void
    {
        $first = in_memory_filesystem();
        $first->write('file1.txt', 'content 1');
        $third = in_memory_filesystem();
        $third->write('file2.txt', 'content 2');
        $fifth = in_memory_filesystem();
        $fifth->write('file3.txt', 'content 3');

        $filesystem = $this->createMultiFilesystem(
            [
                'first' => $first,
                'second' => $this->createMultiFilesystem([
                    'third' => $third,
                ]),
                'fourth' => $this->createMultiFilesystem([
                    'fifth' => $fifth,
                ]),
            ],
        );

        $this->assertSame('content 1', $filesystem->file('first://file1.txt')->contents());
        $this->assertSame('content 2', $filesystem->file('third://file2.txt')->contents());
        $this->assertSame('content 3', $filesystem->file('fifth://file3.txt')->contents());

        $filesystem = $this->createMultiFilesystem(
            [
                '_default_' => $this->createMultiFilesystem([
                    'public' => $first,
                    'private' => $third,
                ], 'public'),
                'fixtures' => $fifth,
            ],
            '_default_'
        );

        $this->assertSame('content 1', $filesystem->file('file1.txt')->contents());
        $this->assertSame('content 2', $filesystem->file('private://file2.txt')->contents());
        $this->assertSame('content 3', $filesystem->file('fixtures://file3.txt')->contents());
    }

    /**
     * @test
     */
    public function can_copy_files_across_filesystems(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('first://foo/bar.txt', 'contents');

        $this->assertTrue($filesystem->has('first://foo/bar.txt'));
        $this->assertFalse($filesystem->has('second://baz/bar.txt'));

        $filesystem->copy('first://foo/bar.txt', 'second://baz/bar.txt');

        $this->assertTrue($filesystem->has('first://foo/bar.txt'));
        $this->assertTrue($filesystem->has('second://baz/bar.txt'));
    }

    /**
     * @test
     */
    public function can_move_files_across_filesystems(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('first://foo/bar.txt', 'contents');

        $this->assertTrue($filesystem->has('first://foo/bar.txt'));
        $this->assertFalse($filesystem->has('second://baz/bar.txt'));

        $filesystem->move('first://foo/bar.txt', 'second://baz/bar.txt');

        $this->assertFalse($filesystem->has('first://foo/bar.txt'));
        $this->assertTrue($filesystem->has('second://baz/bar.txt'));
    }

    final protected function createFilesystem(?array $filesystems = null, ?string $default = null): Filesystem
    {
        if (!$filesystems) {
            $filesystems = [
                'first' => in_memory_filesystem(),
                'second' => in_memory_filesystem(),
            ];

            $default = 'first';
        }

        return $this->createMultiFilesystem($filesystems, $default);
    }

    abstract protected function createMultiFilesystem(array $filesystems, ?string $default = null): MultiFilesystem;
}
