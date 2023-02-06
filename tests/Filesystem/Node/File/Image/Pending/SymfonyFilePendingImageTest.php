<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File\Image\Pending;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Tests\Filesystem\Node\File\Image\PendingImageTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SymfonyFilePendingImageTest extends PendingImageTest
{
    protected function createPendingFile(\SplFileInfo $file, string $filename): PendingImage
    {
        return new PendingImage(new SymfonyFile($file));
    }
}
