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

    public function iptc(): array
    {
        return $this->attributes[Metadata::IPTC] ?? $this->inner()->iptc();
    }

    public function height(): int
    {
        return $this->attributes[Metadata::HEIGHT] ?? $this->inner()->height();
    }

    public function exif(): array
    {
        return $this->attributes[Metadata::EXIF] ?? $this->inner()->exif();
    }

    public function width(): int
    {
        return $this->attributes[Metadata::WIDTH] ?? $this->inner()->width();
    }

    protected function inner(): Image
    {
        return $this->inner ??= $this->filesystem()->image($this->path()); // @phpstan-ignore-line
    }
}
