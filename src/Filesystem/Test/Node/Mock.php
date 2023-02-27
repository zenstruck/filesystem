<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Mock
{
    /**
     * @param ?string                      $filename Optional filename to use (must not include directory separators)
     * @param string|resource|\SplFileInfo $content
     */
    public static function pendingFile(?string $filename = null, ?string $extension = null, mixed $content = null): PendingFile
    {
        return new PendingFile(match (true) {
            null !== $filename => TempFile::withName($filename, $content),
            null !== $content => TempFile::for($content, $extension),
            null !== $extension => TempFile::withExtension($extension),
            default => new TempFile(),
        });
    }

    /**
     * @param ?string $filename Optional filename to use (must not include directory separators)
     */
    public static function pendingImage(int $width = 10, int $height = 10, string $type = 'png', ?string $filename = null): PendingImage
    {
        return new PendingImage(TempFile::image($width, $height, $type, $filename));
    }
}
