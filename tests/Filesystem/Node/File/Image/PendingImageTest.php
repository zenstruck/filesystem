<?php

namespace Zenstruck\Tests\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Tests\Filesystem\Node\File\ImageTests;
use Zenstruck\Tests\Filesystem\Node\File\PendingFileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingImageTest extends PendingFileTest
{
    use ImageTests;

    protected function createPendingFile(\SplFileInfo $file, string $filename): PendingImage
    {
        return new PendingImage($file);
    }

    protected function createFile(\SplFileInfo $file, string $path): Image
    {
        return parent::createFile($file, $path)->ensureImage();
    }
}
