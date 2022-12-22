<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Tests\Filesystem\Node\FileTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class LazyFileTest extends TestCase
{
    use FileTests;

    /**
     * @test
     */
    public function filesystem_must_be_set_for_non_lazy_methods(): void
    {
        $file = $this->createLazyFile('some/path.txt');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Filesystem not set.');

        $file->contents();
    }

    /**
     * @test
     */
    public function path_must_be_set_before_accessing(): void
    {
        $file = $this->createLazyFile();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path not set.');

        $file->path();
    }

    /**
     * @test
     */
    public function path_related_methods_are_lazy(): void
    {
        $file = $this->createLazyFile('some/path.txt');

        $this->assertSame('txt', $file->path()->extension());
        $this->assertSame('txt', $file->guessExtension());
        $this->assertSame('path.txt', $file->path()->name());
        $this->assertSame('path', $file->path()->basename());
    }

    /**
     * @test
     */
    public function can_set_path_and_filesystem(): void
    {
        $filesystem = in_memory_filesystem()->write('some/image.png', 'content');
        $file = $this->createLazyFile();

        $file->setFilesystem($filesystem);
        $file->setPath(fn() => 'some/image.png');

        $this->assertSame('content', $file->contents());
    }

    /**
     * @test
     */
    public function can_use_callable_for_path(): void
    {
        $count = 0;
        $file = $this->createLazyFile(function() use (&$count) {
            ++$count;

            return 'some/image.png';
        });

        $this->assertSame('some/image.png', $file->path()->toString());
        $this->assertSame('some/image.png', $file->path()->toString());
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function can_use_callable_for_filesystem(): void
    {
        $filesystem = in_memory_filesystem()->write('some/image.png', 'content');
        $count = 0;
        $file = $this->createLazyFile('some/image.png');
        $file->setFilesystem(function() use (&$count, $filesystem) {
            ++$count;

            return $filesystem;
        });

        $this->assertSame('content', $file->contents());
        $this->assertSame('content', $file->contents());
        $this->assertSame(1, $count);
    }

    /**
     * @test
     */
    public function can_check_for_existence_without_loading_file(): void
    {
        $file = $this->createLazyFile('some/file.png');
        $file->setFilesystem($filesystem = in_memory_filesystem());

        $this->assertFalse($file->exists());

        $filesystem->write('some/file.png', 'content');

        $this->assertTrue($file->exists());
    }

    protected function createLazyFile(string|callable|null $path = null): LazyFile
    {
        return new LazyFile($path);
    }

    protected function createFile(\SplFileInfo $file, string $path): File
    {
        $lazyFile = new LazyFile($path);
        $lazyFile->setFilesystem($this->filesystem->write($path, $file));

        return $lazyFile;
    }
}
