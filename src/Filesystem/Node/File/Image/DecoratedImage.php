<?php

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Image\BlurHash;
use Zenstruck\Image\LocalImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait DecoratedImage
{
    private ?LocalImage $localImage = null;

    public function transform(callable|object $filter, array $options = []): PendingImage
    {
        return new PendingImage($this->localImage()->transform($filter, $options));
    }

    public function isPortrait(): bool
    {
        return $this->localImage()->isPortrait();
    }

    public function isLandscape(): bool
    {
        return $this->localImage()->isLandscape();
    }

    public function iptc(): array
    {
        return $this->localImage()->iptc();
    }

    public function blurHash(): BlurHash
    {
        return $this->localImage()->blurHash();
    }

    public function height(): int
    {
        return $this->localImage()->height();
    }

    public function aspectRatio(): float
    {
        return $this->localImage()->aspectRatio();
    }

    public function exif(): array
    {
        return $this->localImage()->exif();
    }

    public function width(): int
    {
        return $this->localImage()->width();
    }

    public function pixels(): int
    {
        return $this->localImage()->pixels();
    }

    public function isSquare(): bool
    {
        return $this->localImage()->isSquare();
    }

    public function transformer(string $class): object
    {
        return $this->localImage()->transformer($class);
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
