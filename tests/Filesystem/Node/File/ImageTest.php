<?php

namespace Zenstruck\Tests\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Tests\Filesystem\Node\FileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ImageTest extends FileTest
{
    abstract protected function createFile(\SplFileInfo $file, string $path): Image;
}
