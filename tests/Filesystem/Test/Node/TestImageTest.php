<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Test\Node;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Test\Node\TestImage;
use Zenstruck\Tests\Filesystem\Node\File\ImageTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestImageTest extends TestFileTest
{
    use ImageTests;

    protected function createFile(\SplFileInfo $file, string $path): Image
    {
        return new TestImage($this->filesystem->write($path, $file)->last()->ensureImage());
    }
}
