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
use Zenstruck\Filesystem\Node\File\SerializableFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SerializableImage extends SerializableFile implements Image
{
    use DecoratedImage;

    public function __construct(Image $image, string|array $metadata)
    {
        parent::__construct($image, $metadata);
    }

    protected function inner(): Image
    {
        return parent::inner()->ensureImage();
    }
}
