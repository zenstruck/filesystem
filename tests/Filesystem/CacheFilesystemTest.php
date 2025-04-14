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

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\CacheFilesystem;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CacheFilesystemTest extends FilesystemTest
{
    /**
     * @test
     */
    public function files_are_cached_on_write(): void
    {
        $cacheFs = $this->createFilesystem($fs = in_memory_filesystem());
        $cacheFs->write('some/file.txt', 'content');

        // delete inner filesystem file
        $fs->delete('some/file.txt');

        // file should still be in cache
        $this->assertTrue($cacheFs->has('some/file.txt'));
        $this->assertSame(7, $cacheFs->node('some/file.txt')->size());
        $this->assertFalse($cacheFs->node('some/file.txt')->exists());
    }

    /**
     * @test
     */
    public function files_are_cached_on_fetch(): void
    {
        $cacheFs = $this->createFilesystem($fs = in_memory_filesystem());
        $fs->write('some/file.txt', 'content');

        // fetch file and cache
        $cacheFs->file('some/file.txt');

        // delete inner filesystem file
        $fs->delete('some/file.txt');

        // file should still be in cache
        $this->assertTrue($cacheFs->has('some/file.txt'));
        $this->assertSame(7, $cacheFs->node('some/file.txt')->size());
        $this->assertFalse($cacheFs->node('some/file.txt')->exists());
    }

    /**
     * @test
     */
    public function images_are_cached_on_write(): void
    {
        $cacheFs = $this->createFilesystem($fs = in_memory_filesystem());
        $cacheFs->write('some/file.png', fixture('symfony.png'));

        $fs->delete('some/file.png');

        // image should still be in cache
        $node = $cacheFs->node('some/file.png');
        $this->assertSame(10862, $node->size());
        $this->assertInstanceOf(Image::class, $node);
        $this->assertSame(['width' => 563, 'height' => 678], $node->dimensions()->jsonSerialize());
        $this->assertFalse($node->exists());
    }

    /**
     * @test
     */
    public function images_are_cached_on_fetch(): void
    {
        $cacheFs = $this->createFilesystem($fs = in_memory_filesystem());
        $fs->write('some/file.png', fixture('symfony.png'));

        $cacheFs->image('some/file.png');

        $fs->delete('some/file.png');

        // image should still be in cache
        $node = $cacheFs->node('some/file.png');
        $this->assertSame(10862, $node->size());
        $this->assertInstanceOf(Image::class, $node);
        $this->assertSame(['width' => 563, 'height' => 678], $node->dimensions()->jsonSerialize());
        $this->assertFalse($node->exists());
    }

    protected function createFilesystem(
        ?Filesystem $inner = null,
        ?CacheItemPoolInterface $cache = null,
        array $metadata = [
            Mapping::SIZE,
            Mapping::LAST_MODIFIED,
            Mapping::MIME_TYPE,
            Mapping::DIMENSIONS,
        ],
    ): Filesystem {
        return new CacheFilesystem(
            $inner ?? in_memory_filesystem(),
            $cache ?? new ArrayAdapter(),
            $metadata
        );
    }
}
