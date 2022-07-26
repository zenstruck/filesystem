<?php

namespace Zenstruck\Filesystem\Tests;

use League\Flysystem\ZipArchive\UnableToOpenZipArchive;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\ArchiveFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArchiveFileTest extends FilesystemTest
{
    private const FILE = self::TEMP_DIR.'/archive.zip';

    protected function setUp(): void
    {
        parent::setUp();

        (new SymfonyFilesystem())->remove(self::FILE);
    }

    /**
     * @test
     */
    public function can_create_archive_file_in_non_existent_directory(): void
    {
        (new SymfonyFilesystem())->remove(\dirname(self::FILE));

        $filesystem = new ArchiveFile(self::FILE);
        $filesystem->write('foo.txt', 'contents');

        $this->assertFileExists(self::FILE);
    }

    /**
     * @test
     */
    public function deleting_root_deletes_archive(): void
    {
        $filesystem = new ArchiveFile(self::FILE);
        $filesystem->write('foo.txt', 'contents');

        $this->assertFileExists(self::FILE);

        $filesystem->delete(Filesystem::ROOT);

        $this->assertFileDoesNotExist(self::FILE);
    }

    /**
     * @test
     */
    public function trying_to_read_from_non_existent_archive_does_not_create_the_file(): void
    {
        $filesystem = new ArchiveFile(self::FILE);

        $this->assertFileDoesNotExist(self::FILE);

        $this->assertFalse($filesystem->exists('foo.txt'));

        $this->assertFileDoesNotExist(self::FILE);
    }

    /**
     * @test
     */
    public function cannot_open_invalid_zip(): void
    {
        (new SymfonyFilesystem())->dumpFile(self::FILE, 'not-a-zip');

        $filesystem = new ArchiveFile(self::FILE);

        $this->expectException(UnableToOpenZipArchive::class);

        $filesystem->exists('foo');
    }

    /**
     * @test
     */
    public function can_read_existing_file(): void
    {
        $filesystem = new ArchiveFile(self::FIXTURE_DIR.'/archive.zip');

        $this->assertTrue($filesystem->exists(Filesystem::ROOT));
        $this->assertTrue($filesystem->exists('file1.txt'));
        $this->assertTrue($filesystem->exists('nested/file2.txt'));
        $this->assertCount(3, $filesystem->directory(Filesystem::ROOT)->recursive());
        $this->assertSame('contents 2', $filesystem->file('nested/file2.txt')->contents());
    }

    /**
     * @test
     */
    public function can_wrap_write_operations_in_a_transaction(): void
    {
        $filesystem = new ArchiveFile();
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

        $this->assertTrue($filesystem->exists('file1.txt'));
        $this->assertTrue($filesystem->exists('sub/file2.txt'));
        $this->assertCount(3, $filesystem->directory(Filesystem::ROOT)->recursive());
        $this->assertSame(4, $count);
        $this->assertSame(0.0, $first);
        $this->assertSame(1.0, $last);
    }

    /**
     * @test
     */
    public function can_compress_directory(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_compress_file(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_compress_spl_file(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_compress_spl_directory(): void
    {
        $this->markTestIncomplete();
    }

    protected function createFilesystem(): Filesystem
    {
        return new ArchiveFile();
    }
}
