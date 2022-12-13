<?php

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

        $file->contents();
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

    protected function createLazyFile(string|callable $path): LazyFile
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
