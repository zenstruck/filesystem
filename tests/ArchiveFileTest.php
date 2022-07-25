<?php

namespace Zenstruck\Filesystem\Tests;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\ArchiveFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArchiveFileTest extends FilesystemTest
{
    /**
     * @test
     */
    public function can_create_archive_file_in_non_existent_directory(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function deleting_root_deletes_archive(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function trying_to_read_from_non_existent_archive_does_not_create_the_file(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function cannot_open_invalid_zip(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_read_existing_file(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_wrap_write_operations_in_a_transaction(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_use_commit_callback(): void
    {
        $this->markTestIncomplete();
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
