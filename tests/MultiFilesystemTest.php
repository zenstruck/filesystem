<?php

namespace Zenstruck\Filesystem\Tests;

use Zenstruck\Filesystem;

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
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_copy_files_across_filesystems(): void
    {
        $this->markTestIncomplete();

        $filesystem = $this->createFilesystem();
        $filesystem->write('first://foo/bar.txt', 'contents');

        $this->assertTrue($filesystem->exists('first://foo/bar.txt'));
        $this->assertFalse($filesystem->exists('second://baz/bar.txt'));

        $filesystem->copy('first://foo/bar.txt', 'second://baz/bar.txt');

        $this->assertTrue($filesystem->exists('first://foo/bar.txt'));
        $this->assertTrue($filesystem->exists('second://baz/bar.txt'));
    }

    /**
     * @test
     */
    public function can_move_files_across_filesystems(): void
    {
        $this->markTestIncomplete();

        $filesystem = $this->createFilesystem();
        $filesystem->write('first://foo/bar.txt', 'contents');

        $this->assertTrue($filesystem->exists('first://foo/bar.txt'));
        $this->assertFalse($filesystem->exists('second://baz/bar.txt'));

        $filesystem->move('first://foo/bar.txt', 'second://baz/bar.txt');

        $this->assertFalse($filesystem->exists('first://foo/bar.txt'));
        $this->assertTrue($filesystem->exists('second://baz/bar.txt'));
    }

    /**
     * @test
     */
    public function can_copy_directories_across_filesystems(): void
    {
        $this->markTestIncomplete();

        $filesystem = $this->createFilesystem();
        $filesystem->write('first://foo/bar.txt', 'contents');
        $filesystem->write('first://foo/nested/bar.txt', 'contents');

        $this->assertTrue($filesystem->exists('first://foo/bar.txt'));
        $this->assertTrue($filesystem->exists('first://foo/nested/bar.txt'));
        $this->assertFalse($filesystem->exists('second://baz/bar.txt'));
        $this->assertFalse($filesystem->exists('second://baz/nested/bar.txt'));

        $filesystem->copy('first://foo', 'second://baz');

        $this->assertTrue($filesystem->exists('first://foo/bar.txt'));
        $this->assertTrue($filesystem->exists('first://foo/nested/bar.txt'));
        $this->assertTrue($filesystem->exists('second://baz/bar.txt'));
        $this->assertTrue($filesystem->exists('second://baz/nested/bar.txt'));
    }

    /**
     * @test
     */
    public function can_move_directories_across_filesystems(): void
    {
        $this->markTestIncomplete();

        $filesystem = $this->createFilesystem();
        $filesystem->write('first://foo/bar.txt', 'contents');
        $filesystem->write('first://foo/nested/bar.txt', 'contents');

        $this->assertTrue($filesystem->exists('first://foo/bar.txt'));
        $this->assertTrue($filesystem->exists('first://foo/nested/bar.txt'));
        $this->assertFalse($filesystem->exists('second://baz/bar.txt'));
        $this->assertFalse($filesystem->exists('second://baz/nested/bar.txt'));

        $filesystem->move('first://foo', 'second://baz');

        $this->assertFalse($filesystem->exists('first://foo/bar.txt'));
        $this->assertFalse($filesystem->exists('first://foo/nested/bar.txt'));
        $this->assertTrue($filesystem->exists('second://baz/bar.txt'));
        $this->assertTrue($filesystem->exists('second://baz/nested/bar.txt'));
    }

    protected function createFilesystem(): Filesystem
    {
        $this->markTestIncomplete();
    }
}
