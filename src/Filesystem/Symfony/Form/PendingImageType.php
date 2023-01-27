<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\Form;

use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingImageType extends PendingFileType
{
    protected static function pendingFileType(): string
    {
        return PendingImage::class;
    }

    protected static function pendingFileFactory(\SplFileInfo $file): PendingFile
    {
        return new PendingImage($file);
    }
}
