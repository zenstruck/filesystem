<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Command;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Console\Test\InteractsWithConsole;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Test\ResetFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FilesystemPurgeCommandTest extends KernelTestCase
{
    use InteractsWithConsole, InteractsWithFilesystem, ResetFilesystem;

    protected function setUp(): void
    {
        $this->filesystem()
            ->write('file.txt', 'content')
            ->write('foo/file.txt', 'content')
            ->write('foo/bar/file.txt', 'content')
            ->write('file2.txt', 'content')
        ;

        \touch(TEMP_DIR.'/../public/foo/bar/file.txt', (new \DateTime('-45 days'))->getTimestamp());
    }

    /**
     * @test
     */
    public function run_no_filter(): void
    {
        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public')
            ->execute()
            ->assertOutputContains('Deleted 2 files')
            ->assertOutputNotContains('file2.txt')
        ;

        $this->filesystem()
            ->assertNotExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertNotExists('file2.txt')
        ;
    }

    /**
     * @test
     */
    public function run_no_filter_verbose(): void
    {
        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public -v')
            ->execute()
            ->assertOutputContains('Deleted 2 files')
            ->assertOutputContains('file2.txt')
        ;

        $this->filesystem()
            ->assertNotExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertNotExists('file2.txt')
        ;
    }

    /**
     * @test
     */
    public function run_with_dir_no_filter(): void
    {
        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public foo')
            ->execute()
            ->assertOutputContains('Deleted 1 files')
        ;

        $this->filesystem()
            ->assertExists('file.txt')
            ->assertNotExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;
    }

    /**
     * @test
     */
    public function run_recursive_no_filter(): void
    {
        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public -r')
            ->execute()
            ->assertOutputContains('Deleted 4 files')
        ;

        $this->filesystem()
            ->assertNotExists('file.txt')
            ->assertNotExists('foo/file.txt')
            ->assertNotExists('foo/bar/file.txt')
            ->assertNotExists('file2.txt')
            ->assertExists('foo')
            ->assertExists('foo/bar')
        ;
    }

    /**
     * @test
     */
    public function run_older_than_filter(): void
    {
        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public --older-than="30 days"')
            ->execute()
            ->assertOutputContains('Deleted 0 files')
        ;

        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;
    }

    /**
     * @test
     */
    public function run_recursive_older_than_filter(): void
    {
        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public -r --older-than="30 days"')
            ->execute()
            ->assertOutputContains('Deleted 1 files')
        ;

        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertNotExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;
    }

    /**
     * @test
     */
    public function run_remove_empty_directories(): void
    {
        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public -r --remove-empty-directories')
            ->execute()
            ->assertOutputContains('Deleted 4 files')
            ->assertOutputNotContains('foo/bar')
            ->assertOutputContains('Deleted 1 empty directories')
        ;

        $this->filesystem()
            ->assertNotExists('file.txt')
            ->assertNotExists('foo/file.txt')
            ->assertNotExists('foo/bar/file.txt')
            ->assertNotExists('file2.txt')
            ->assertExists('foo') // doesn't look up recursively
            ->assertNotExists('foo/bar')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public -r --remove-empty-directories')
            ->execute()
            ->assertOutputContains('Deleted 0 files')
            ->assertOutputNotContains('foo')
            ->assertOutputContains('Deleted 1 empty directories')
        ;

        $this->filesystem()
            ->assertNotExists('file.txt')
            ->assertNotExists('foo/file.txt')
            ->assertNotExists('foo/bar/file.txt')
            ->assertNotExists('file2.txt')
            ->assertNotExists('foo')
            ->assertNotExists('foo/bar')
        ;
    }

    /**
     * @test
     */
    public function run_remove_empty_directories_verbose(): void
    {
        $this->filesystem()
            ->assertExists('file.txt')
            ->assertExists('foo/file.txt')
            ->assertExists('foo/bar/file.txt')
            ->assertExists('file2.txt')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public -r -v --remove-empty-directories')
            ->execute()
            ->assertOutputContains('Deleted 4 files')
            ->assertOutputContains('foo/bar')
            ->assertOutputContains('Deleted 1 empty directories')
        ;

        $this->filesystem()
            ->assertNotExists('file.txt')
            ->assertNotExists('foo/file.txt')
            ->assertNotExists('foo/bar/file.txt')
            ->assertNotExists('file2.txt')
            ->assertExists('foo') // doesn't look up recursively
            ->assertNotExists('foo/bar')
        ;

        $this
            ->consoleCommand('zenstruck:filesystem:purge public -r -v --remove-empty-directories')
            ->execute()
            ->assertOutputContains('Deleted 0 files')
            ->assertOutputContains('foo')
            ->assertOutputContains('Deleted 1 empty directories')
        ;

        $this->filesystem()
            ->assertNotExists('file.txt')
            ->assertNotExists('foo/file.txt')
            ->assertNotExists('foo/bar/file.txt')
            ->assertNotExists('file2.txt')
            ->assertNotExists('foo')
            ->assertNotExists('foo/bar')
        ;
    }
}
