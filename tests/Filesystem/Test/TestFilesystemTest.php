<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Test;

use Zenstruck\Filesystem\Test\Node\TestDirectory;
use Zenstruck\Filesystem\Test\Node\TestFile;
use Zenstruck\Filesystem\Test\Node\TestImage;
use Zenstruck\Filesystem\Test\TestFilesystem;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestFilesystemTest extends FilesystemTest
{
    /**
     * @test
     */
    public function can_make_assertions(): void
    {
        $filesystem = $this->createFilesystem()
            ->mkdir('foo')
            ->write('file1.txt', 'contents1')
            ->write('nested/file2.txt', 'contents1')
            ->write('symfony.png', fixture('symfony.png'))
        ;

        $filesystem
            ->assertExists('foo')
            ->assertNotExists('invalid')
            ->assertFileExists('file1.txt')
            ->assertDirectoryExists('foo')
            ->assertImageExists('symfony.png')
            ->assertSame('file1.txt', 'nested/file2.txt')
            ->assertNotSame('file1.txt', 'symfony.png')
            ->assertDirectoryExists('', function(TestDirectory $dir) {
                $dir
                    ->assertCount(4)
                    ->files()->assertCount(2)
                ;

                $dir
                    ->recursive()
                    ->assertCount(5)
                    ->files()->assertCount(3)
                ;
            })
            ->assertFileExists('file1.txt', function(TestFile $file) {
                $file
                    ->assertVisibilityIs('public')
                    ->assertChecksum($file->checksum())
                    ->assertContentIs('contents1')
                    ->assertContentIsNot('foo')
                    ->assertContentContains('1')
                    ->assertContentDoesNotContain('foo')
                    ->assertMimeTypeIs('text/plain')
                    ->assertMimeTypeIsNot('foo')
                    ->assertLastModified(function(\DateTimeInterface $actual) {
                        $this->assertTrue($actual->getTimestamp() > 0);
                    })
                    ->assertSize(9)
                ;
            })
            ->assertImageExists('symfony.png', function(TestImage $image) {
                $image
                    ->assertHeight(678)
                    ->assertWidth(563)
                ;
            })
        ;
    }

    /**
     * @test
     */
    public function can_create_real_file(): void
    {
        $file = $this->createFilesystem()->write('file1.txt', 'contents')->realFile('file1.txt');

        $this->assertFileExists($file);
        $this->assertSame('/tmp/file1.txt', (string) $file);
        $this->assertSame('contents', \file_get_contents($file));
    }

    protected function createFilesystem(): TestFilesystem
    {
        return new TestFilesystem(in_memory_filesystem());
    }
}
