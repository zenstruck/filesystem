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
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\Metadata;
use Zenstruck\Image\Dimensions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyImage extends LazyFile implements Image
{
    use DecoratedImage;

    public function transformUrl(array|string $filter): string
    {
        if (\is_string($filter) && isset($this->attributes[Metadata::TRANSFORM_URL][$filter])) {
            return $this->attributes[Metadata::TRANSFORM_URL][$filter];
        }

        return $this->inner()->transformUrl($filter);
    }

    public function dimensions(): Dimensions
    {
        if (!isset($this->attributes[Metadata::DIMENSIONS])) {
            return $this->inner()->dimensions();
        }

        $dimensions = $this->attributes[Metadata::DIMENSIONS];

        if ($dimensions instanceof Dimensions) {
            return $dimensions;
        }

        $dimensions[0] = $dimensions['width'] ?? $dimensions[0];
        $dimensions[1] = $dimensions['height'] ?? $dimensions[1];

        return $this->attributes[Metadata::DIMENSIONS] = new Dimensions($dimensions);
    }

    public function iptc(): array
    {
        return $this->attributes[Metadata::IPTC] ?? $this->inner()->iptc();
    }

    public function exif(): array
    {
        return $this->attributes[Metadata::EXIF] ?? $this->inner()->exif();
    }

    protected function inner(): Image
    {
        return $this->inner ??= $this->filesystem()->image($this->path()); // @phpstan-ignore-line
    }
}
