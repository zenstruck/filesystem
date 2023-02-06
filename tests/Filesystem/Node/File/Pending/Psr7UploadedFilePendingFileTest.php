<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File\Pending;

use GuzzleHttp\Psr7\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\TempFile;
use Zenstruck\Tests\Filesystem\Node\File\PendingFileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Psr7UploadedFilePendingFileTest extends PendingFileTest
{
    protected function createPendingFile(\SplFileInfo $file, string $filename): PendingFile
    {
        $file = TempFile::for($file);
        \chmod($file, 0644);

        return new PendingFile(new UploadedFile(
            (string) $file,
            size: $file->getSize(),
            errorStatus: \UPLOAD_ERR_OK,
            clientFilename: $filename,
            clientMediaType: MimeTypes::getDefault()->guessMimeType($file),
        ));
    }
}
