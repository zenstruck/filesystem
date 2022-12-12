<?php

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
