<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\PlaceholderFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PlaceholderImage extends PlaceholderFile implements Image
{
    use DecoratedImage;

    protected function inner(): Image
    {
        throw new \LogicException('This is a placeholder image only.');
    }
}
