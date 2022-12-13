<?php

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
