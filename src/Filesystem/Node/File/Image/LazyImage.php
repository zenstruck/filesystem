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
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Image\Dimensions;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LazyImage extends LazyFile implements Image
{
    use DecoratedImage;

    public function transformUrl(array|string $filter): string
    {
        if (\is_string($filter) && isset($this->attributes[Mapping::TRANSFORM_URL][$filter])) {
            return $this->attributes[Mapping::TRANSFORM_URL][$filter];
        }

        return $this->inner()->transformUrl($filter);
    }

    public function dimensions(): Dimensions
    {
        if (!isset($this->attributes[Mapping::DIMENSIONS])) {
            return $this->inner()->dimensions();
        }

        $dimensions = $this->attributes[Mapping::DIMENSIONS];

        if ($dimensions instanceof Dimensions) {
            return $dimensions;
        }

        return $this->attributes[Mapping::DIMENSIONS] = new Dimensions($dimensions);
    }

    public function iptc(): array
    {
        return $this->attributes[Mapping::IPTC] ?? $this->inner()->iptc();
    }

    public function exif(): array
    {
        return $this->attributes[Mapping::EXIF] ?? $this->inner()->exif();
    }

    public function ensureImage(): self
    {
        return $this;
    }

    protected function inner(): Image
    {
        return $this->inner ??= $this->filesystem()->image($this->path()); // @phpstan-ignore-line
    }
}
