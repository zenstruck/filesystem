<?php

namespace Zenstruck\Tests\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Tests\Filesystem\Node\FileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyFileTest extends FileTest
{
    /**
     * @test
     */
    public function filesystem_must_be_set_for_non_lazy_methods(): void
    {
        $file = new LazyFile('some/path.txt');

        $this->expectException(\RuntimeException::class);

        $file->contents();
    }

    protected function createFile(\SplFileInfo $file, string $path): File
    {
        $lazyFile = new LazyFile($path);
        $lazyFile->setFilesystem($this->filesystem->write($path, $file));

        return $lazyFile;
    }
}
