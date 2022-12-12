<?php

namespace Zenstruck\Tests\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ImageTests
{
    abstract protected function createFile(\SplFileInfo $file, string $path): Image;
}
