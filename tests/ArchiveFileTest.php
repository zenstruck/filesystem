<?php

namespace Zenstruck\Filesystem\Tests;

use League\Flysystem\ZipArchive\UnableToOpenZipArchive;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\ArchiveFile;
use Zenstruck\Filesystem\Util;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArchiveFileTest extends FilesystemTest
{
    private const FILE = self::TEMP_DIR.'/archive.zip';

    /**
     * @test
     */
    public function can_create_archive_file_in_non_existent_directory(): void
    {
        Util::fs()->remove(\dirname(self::FILE));

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
        Util::fs()->dumpFile(self::FILE, 'not-a-zip');

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
    public function can_zip_directory(): void
    {
        $dir = $this->filesystem()
            ->write('sub/file1.txt', 'contents 1')
            ->write('sub/nested/file2.txt', 'contents 2')
            ->directory('sub')
            ->recursive()
        ;

        $archive = ArchiveFile::zip($dir);

        $this->assertFileExists($archive);

        $this->assertSame('contents 1', $archive->file('file1.txt')->contents());
        $this->assertSame('contents 2', $archive->file('nested/file2.txt')->contents());
    }

    /**
     * @test
     */
    public function can_zip_file(): void
    {
        $file = $this->filesystem()->write('nested/file.txt', 'contents')->last();

        $archive = ArchiveFile::zip($file);

        $this->assertFileExists($archive);

        $this->assertSame('contents', $archive->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_zip_spl_file(): void
    {
        Util::fs()->dumpFile($file = self::TEMP_DIR.'/file.txt', 'contents');

        $archive = ArchiveFile::zip($file);

        $this->assertFileExists($archive);

        $this->assertSame('contents', $archive->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_zip_spl_directory(): void
    {
        Util::fs()->dumpFile(self::TEMP_DIR.'/file1.txt', 'contents 1');
        Util::fs()->dumpFile(self::TEMP_DIR.'/nested/file2.txt', 'contents 2');

        $archive = ArchiveFile::zip(self::TEMP_DIR);

        $this->assertFileExists($archive);

        $this->assertSame('contents 1', $archive->file('file1.txt')->contents());
        $this->assertSame('contents 2', $archive->file('nested/file2.txt')->contents());
    }

    /**
     * @test
     */
    public function can_tar_directory(): void
    {
        $dir = $this->filesystem()
            ->write('sub/file1.txt', 'contents 1')
            ->write('sub/nested/file2.txt', 'contents 2')
            ->directory('sub')
            ->recursive()
        ;

        $archive = ArchiveFile::tar($dir, self::TEMP_DIR.'/archive1.tar');

        $this->assertFileExists($archive);
        $this->assertSame('application/x-tar', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_file(): void
    {
        $file = $this->filesystem()->write('nested/file.txt', 'contents')->last();

        $archive = ArchiveFile::tar($file, self::TEMP_DIR.'/archive2.tar');

        $this->assertFileExists($archive);
        $this->assertSame('application/x-tar', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_spl_file(): void
    {
        Util::fs()->dumpFile($file = self::TEMP_DIR.'/file.txt', 'contents');

        $archive = ArchiveFile::tar($file, self::TEMP_DIR.'/archive3.tar');

        $this->assertFileExists($archive);
        $this->assertSame('application/x-tar', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_spl_directory(): void
    {
        Util::fs()->dumpFile(self::TEMP_DIR.'/file1.txt', 'contents 1');
        Util::fs()->dumpFile(self::TEMP_DIR.'/nested/file2.txt', 'contents 2');

        $archive = ArchiveFile::tar(self::TEMP_DIR, self::TEMP_DIR.'/archive4.tar');

        $this->assertFileExists($archive);
        $this->assertSame('application/x-tar', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_gz_directory(): void
    {
        $dir = $this->filesystem()
            ->write('sub/file1.txt', 'contents 1')
            ->write('sub/nested/file2.txt', 'contents 2')
            ->directory('sub')
            ->recursive()
        ;

        $archive = ArchiveFile::tarGz($dir, self::TEMP_DIR.'/archive5.tar.gz');

        $this->assertFileExists($archive);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/archive5.tar');
        $this->assertSame('application/gzip', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_gz_file(): void
    {
        $file = $this->filesystem()->write('nested/file.txt', 'contents')->last();

        $archive = ArchiveFile::tarGz($file, self::TEMP_DIR.'/archive6.tar.gz');

        $this->assertFileExists($archive);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/archive6.tar');
        $this->assertSame('application/gzip', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_gz_spl_file(): void
    {
        Util::fs()->dumpFile($file = self::TEMP_DIR.'/file.txt', 'contents');

        $archive = ArchiveFile::tarGz($file, self::TEMP_DIR.'/archive7.tar.gz');

        $this->assertFileExists($archive);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/archive7.tar');
        $this->assertSame('application/gzip', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_gz_spl_directory(): void
    {
        Util::fs()->dumpFile(self::TEMP_DIR.'/file1.txt', 'contents 1');
        Util::fs()->dumpFile(self::TEMP_DIR.'/nested/file2.txt', 'contents 2');

        $archive = ArchiveFile::tarGz(self::TEMP_DIR, self::TEMP_DIR.'/archive8.tar.gz');

        $this->assertFileExists($archive);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/archive8.tar');
        $this->assertSame('application/gzip', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_bz2_directory(): void
    {
        $dir = $this->filesystem()
            ->write('sub/file1.txt', 'contents 1')
            ->write('sub/nested/file2.txt', 'contents 2')
            ->directory('sub')
            ->recursive()
        ;

        $archive = ArchiveFile::tarBz2($dir, self::TEMP_DIR.'/archive9.tar.bz2');

        $this->assertFileExists($archive);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/archive9.tar');
        $this->assertSame('application/x-bzip2', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_bz2_file(): void
    {
        $file = $this->filesystem()->write('nested/file.txt', 'contents')->last();

        $archive = ArchiveFile::tarBz2($file, self::TEMP_DIR.'/archive10.tar.bz2');

        $this->assertFileExists($archive);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/archive10.tar');
        $this->assertSame('application/x-bzip2', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_bz2_spl_file(): void
    {
        Util::fs()->dumpFile($file = self::TEMP_DIR.'/file.txt', 'contents');

        $archive = ArchiveFile::tarBz2($file, self::TEMP_DIR.'/archive11.tar.bz2');

        $this->assertFileExists($archive);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/archive11.tar');
        $this->assertSame('application/x-bzip2', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    /**
     * @test
     */
    public function can_tar_bz2_spl_directory(): void
    {
        Util::fs()->dumpFile(self::TEMP_DIR.'/file1.txt', 'contents 1');
        Util::fs()->dumpFile(self::TEMP_DIR.'/nested/file2.txt', 'contents 2');

        $archive = ArchiveFile::tarBz2(self::TEMP_DIR, self::TEMP_DIR.'/archive12.tar.bz2');

        $this->assertFileExists($archive);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/archive12.tar');
        $this->assertSame('application/x-bzip2', (new FinfoMimeTypeDetector())->detectMimeTypeFromFile($archive));
    }

    protected function createFilesystem(): Filesystem
    {
        return new ArchiveFile();
    }
}
