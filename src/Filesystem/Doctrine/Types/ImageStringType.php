<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine\Types;

use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\LazyFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ImageStringType extends FileStringType
{
    public const NAME = 'zs_image';

    protected function createFile(string $path): LazyFile
    {
        return new LazyImage($path);
    }
}
