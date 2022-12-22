<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    protected function createLazyFile(string|callable|null $path = null): LazyImage
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
