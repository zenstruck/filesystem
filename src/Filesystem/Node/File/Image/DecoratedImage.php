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
use Zenstruck\Image\Dimensions;
use Zenstruck\ImageFileInfo;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait DecoratedImage
{
    public function transformUrl(array|string $filter): string
    {
        return $this->inner()->transformUrl($filter);
    }

    public function transform(callable|object $filter, array $options = []): PendingImage
    {
        return $this->inner()->transform($filter, $options);
    }

    public function dimensions(): Dimensions
    {
        return $this->inner()->dimensions();
    }

    public function iptc(): array
    {
        return $this->inner()->iptc();
    }

    public function exif(): array
    {
        return $this->inner()->exif();
    }

    public function tempFile(): ImageFileInfo
    {
        return $this->inner()->tempFile();
    }

    abstract protected function inner(): Image;
}
