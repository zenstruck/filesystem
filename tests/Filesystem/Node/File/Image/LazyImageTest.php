<?php

namespace Zenstruck\Tests\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Tests\Filesystem\Node\File\ImageTests;
use Zenstruck\Tests\Filesystem\Node\File\LazyFileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyImageTest extends LazyFileTest
{
    use ImageTests;

    protected function createLazyFile(string $path): LazyImage
    {
        return new LazyImage($path);
    }

    protected function createFile(\SplFileInfo $file, string $path): Image
    {
        $image = new LazyImage($path);
        $image->setFilesystem($this->filesystem->write($path, $file));

        return $image;
    }
}
