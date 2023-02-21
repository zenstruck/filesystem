<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Archive;

use League\Flysystem\ZipArchive\UnableToOpenZipArchive;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Archive\ZipFile;
use Zenstruck\Tests\FilesystemTest;
use Zenstruck\Tests\InteractsWithTempDirectory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZipFileTest extends FilesystemTest
{
    use InteractsWithTempDirectory;

    private const FILE = TEMP_DIR.'/some/archive.zip';

    /**
     * @test
     */
    public function name(): void
    {
        $filesystem = new ZipFile(self::FILE);

        $this->assertSame('zip://'.self::FILE, $filesystem->name());
    }

    /**
     * @test
     */
    public function can_create_archive_file_in_non_existent_directory(): void
    {
        $filesystem = new ZipFile(self::FILE);
        $filesystem->write('foo.txt', 'contents');

        $this->assertFileExists(self::FILE);
    }

    /**
     * @test
     */
    public function deleting_root_deletes_archive(): void
    {
        $filesystem = new ZipFile(self::FILE);
        $filesystem->write('foo.txt', 'contents');

        $this->assertFileExists(self::FILE);

        $filesystem->delete();

        $this->assertFileDoesNotExist(self::FILE);
    }

    /**
     * @test
     */
    public function trying_to_read_from_non_existent_archive_does_not_create_the_file(): void
    {
        $filesystem = new ZipFile(self::FILE);

        $this->assertFileDoesNotExist(self::FILE);

        $this->assertFalse($filesystem->has('foo.txt'));

        $this->assertFileDoesNotExist(self::FILE);
    }

    /**
     * @test
     */
    public function cannot_open_invalid_zip(): void
    {
        \file_put_contents($file = TEMP_DIR.'/archive.zip', 'invalid content');

        $filesystem = new ZipFile($file);

        $this->expectException(UnableToOpenZipArchive::class);

        $filesystem->has('foo');
    }

    /**
     * @test
     */
    public function can_read_existing_file(): void
    {
        $filesystem = new ZipFile(fixture('archive.zip'));

        $this->assertTrue($filesystem->has());
        $this->assertTrue($filesystem->has('file1.txt'));
        $this->assertTrue($filesystem->has('nested/file2.txt'));
        $this->assertCount(3, $filesystem->directory()->recursive());
        $this->assertSame('contents 2', $filesystem->file('nested/file2.txt')->contents());
    }

    /**
     * @test
     */
    public function can_wrap_write_operations_in_a_transaction(): void
    {
        $filesystem = new ZipFile();
        $filesystem->beginTransaction();
        $filesystem->write('file1.txt', 'contents1');
        $filesystem->write('sub/file2.txt', 'contents2');

        $count = 0;
        $first = null;
        $last = null;

        $filesystem->commit(function($current) use (&$first, &$last, &$count) {
            if (null === $first) {
                $first = $current;
            }

            $last = $current;
            ++$count;
        });

        $this->assertTrue($filesystem->has('file1.txt'));
        $this->assertTrue($filesystem->has('sub/file2.txt'));
        $this->assertCount(3, $filesystem->directory()->recursive());
        $this->assertSame(4, $count);
        $this->assertSame(0.0, $first);
        $this->assertSame(1.0, $last);
    }

    /**
     * @test
     */
    public function can_zip_directory(): void
    {
        $fs = in_memory_filesystem();
        $fs->write('sub/file1.txt', 'contents 1');
        $fs->write('sub/nested/file2.txt', 'contents 2');
        $dir = $fs->directory('sub')->recursive();

        $archive = ZipFile::zip($dir);

        $this->assertFileExists($archive);

        $this->assertSame('contents 1', $archive->file('file1.txt')->contents());
        $this->assertSame('contents 2', $archive->file('nested/file2.txt')->contents());
    }

    /**
     * @test
     */
    public function can_zip_file(): void
    {
        $file = in_memory_filesystem()->write('nested/file.txt', 'contents');

        $archive = ZipFile::zip($file);

        $this->assertFileExists($archive);

        $this->assertSame('contents', $archive->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_zip_spl_file(): void
    {
        \file_put_contents($file = TEMP_DIR.'/file.txt', 'contents');

        $archive = ZipFile::zip($file);

        $this->assertFileExists($archive);

        $this->assertSame('contents', $archive->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_zip_spl_directory(): void
    {
        $fs = temp_filesystem();
        $fs->write('file1.txt', 'contents 1');
        $fs->write('nested/file2.txt', 'contents 2');

        $archive = ZipFile::zip(TEMP_DIR);

        $this->assertFileExists($archive);

        $this->assertSame('contents 1', $archive->file('file1.txt')->contents());
        $this->assertSame('contents 2', $archive->file('nested/file2.txt')->contents());
    }

    protected function createFilesystem(): Filesystem
    {
        return new ZipFile();
    }
}
