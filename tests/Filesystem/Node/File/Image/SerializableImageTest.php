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
use Zenstruck\Filesystem\Node\File\Image\SerializableImage;
use Zenstruck\Tests\Filesystem\Node\File\ImageTests;
use Zenstruck\Tests\Filesystem\Node\File\SerializableFileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SerializableImageTest extends SerializableFileTest
{
    use ImageTests;

    protected function createFile(\SplFileInfo $file, string $path): Image
    {
        return new SerializableImage($this->filesystem->write($path, $file)->last()->ensureImage(), []);
    }
}
