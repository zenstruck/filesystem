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
use Zenstruck\Tests\Filesystem\Node\File\FlysystemFileTest;
use Zenstruck\Tests\Filesystem\Node\File\ImageTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemImageTest extends FlysystemFileTest
{
    use ImageTests;

    protected function createFile(\SplFileInfo $file, string $path): Image
    {
        return $this->filesystem->write($path, $file)->last()->ensureImage();
    }
}
