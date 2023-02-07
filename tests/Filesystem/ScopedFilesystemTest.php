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

use Zenstruck\Filesystem\ScopedFilesystem;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScopedFilesystemTest extends FilesystemTest
{
    /**
     * @test
     */
    public function operations_are_scoped(): void
    {
        $filesystem = new ScopedFilesystem(in_memory_filesystem(), 'foo/bar');

        $filesystem->write('baz/qux.txt', '');
        $filesystem->write('file.txt', '');
        $filesystem->copy('/baz/qux.txt', 'qux.txt');
        $filesystem->move('file.txt', 'faz/qux.txt');
        $filesystem->chmod('faz/qux.txt', 'private');
        $filesystem->mkdir('bar');

        $this->assertSame('foo/bar/baz/qux.txt', $filesystem->node('baz/qux.txt')->path()->toString());
        $this->assertSame('foo/bar/baz/qux.txt', $filesystem->file('baz/qux.txt')->path()->toString());
        $this->assertSame('foo/bar/baz', $filesystem->directory('baz')->path()->toString());
        $this->assertSame('foo/bar/qux.txt', $filesystem->node('qux.txt')->path()->toString());
        $this->assertSame('foo/bar/faz/qux.txt', $filesystem->node('faz/qux.txt')->path()->toString());
        $this->assertSame('private', $filesystem->node('faz/qux.txt')->visibility());
        $this->assertSame('foo/bar/bar', $filesystem->directory('bar')->path()->toString());

        $filesystem->delete('bar');

        $this->assertFalse($filesystem->has('bar'));
    }

    /**
     * @test
     */
    public function prefix_is_removed(): void
    {
        $filesystem = new ScopedFilesystem(in_memory_filesystem(), 'foo/bar');

        $filesystem->write('foo/bar/baz/qux.txt', '');
        $filesystem->write('foo/bar/file.txt', '');
        $filesystem->copy('/foo/bar/baz/qux.txt', 'foo/bar/qux.txt');
        $filesystem->move('foo/bar/file.txt', 'foo/bar/faz/qux.txt');
        $filesystem->chmod('foo/bar/faz/qux.txt', 'private');
        $filesystem->mkdir('foo/bar/bar');

        $this->assertSame('foo/bar/baz/qux.txt', $filesystem->node('baz/qux.txt')->path()->toString());
        $this->assertSame('foo/bar/baz/qux.txt', $filesystem->file('baz/qux.txt')->path()->toString());
        $this->assertSame('foo/bar/baz', $filesystem->directory('baz')->path()->toString());
        $this->assertSame('foo/bar/qux.txt', $filesystem->node('qux.txt')->path()->toString());
        $this->assertSame('foo/bar/faz/qux.txt', $filesystem->node('faz/qux.txt')->path()->toString());
        $this->assertSame('private', $filesystem->node('faz/qux.txt')->visibility());
        $this->assertSame('foo/bar/bar', $filesystem->directory('bar')->path()->toString());

        $filesystem->delete('bar');

        $this->assertFalse($filesystem->has('bar'));
    }

    /**
     * @test
     */
    public function default_name(): void
    {
        $filesystem = new ScopedFilesystem(in_memory_filesystem('primary'), 'foo/bar');

        $this->assertSame('primary-scoped-to-foo/bar', $filesystem->name());
    }

    /**
     * @test
     */
    public function customize_name(): void
    {
        $filesystem = new ScopedFilesystem(in_memory_filesystem('primary'), 'foo/bar', 'scoped');

        $this->assertSame('scoped', $filesystem->name());
    }

    /**
     * @test
     */
    public function last_fails_if_not_performed_on_self(): void
    {
        $primary = in_memory_filesystem();
        $scoped = new ScopedFilesystem($primary, 'foo/bar');

        $scoped->write('baz.txt', 'content');

        $this->assertSame('foo/bar/baz.txt', $scoped->last()->path()->toString());

        $primary->write('something.txt', 'content');

        $this->expectException(\LogicException::class);

        $scoped->last();
    }

    protected function createFilesystem(): ScopedFilesystem
    {
        return new ScopedFilesystem(in_memory_filesystem(), '');
    }
}
