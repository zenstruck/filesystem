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

use Zenstruck\Image as LocalImage;
use Zenstruck\Image\CalculatedProperties;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait DecoratedImage
{
    use CalculatedProperties;

    private ?LocalImage $localImage = null;

    public function transformUrl(array|string $filter): string
    {
        return $this->inner()->transformUrl($filter);
    }

    public function transform(callable|object $filter, array $options = []): PendingImage
    {
        return $this->inner()->transform($filter, $options);
    }

    public function iptc(): array
    {
        return $this->localImage()->iptc();
    }

    public function height(): int
    {
        return $this->localImage()->height();
    }

    public function exif(): array
    {
        return $this->localImage()->exif();
    }

    public function width(): int
    {
        return $this->localImage()->width();
    }

    public function tempFile(): LocalImage
    {
        return new LocalImage(parent::tempFile());
    }

    public function refresh(): static
    {
        $this->localImage?->refresh();

        return parent::refresh();
    }

    protected function localImage(): LocalImage
    {
        return $this->localImage ??= $this->tempFile();
    }
}
