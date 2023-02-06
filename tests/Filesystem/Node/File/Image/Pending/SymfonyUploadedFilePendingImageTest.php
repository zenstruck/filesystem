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

use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\TempFile;
use Zenstruck\Tests\Filesystem\Node\File\Image\PendingImageTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SymfonyUploadedFilePendingImageTest extends PendingImageTest
{
    protected function createPendingFile(\SplFileInfo $file, string $filename): PendingImage
    {
        $file = TempFile::for($file);
        \chmod($file, 0644);

        return new PendingImage(new SymfonyUploadedFile(
            $file,
            $filename,
            MimeTypes::getDefault()->guessMimeType($file),
            test: true
        ));
    }
}
