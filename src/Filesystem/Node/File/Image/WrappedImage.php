<?php

namespace Zenstruck\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\File\WrappedFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait WrappedImage
{
    use WrappedFile;

    public function transform(callable $manipulator, array $options = []): PendingFile
    {
        return $this->inner()->transform($manipulator, $options);
    }

    public function height(): int
    {
        return $this->inner()->height();
    }

    public function width(): int
    {
        return $this->inner()->width();
    }

    public function aspectRatio(): float
    {
        return $this->inner()->aspectRatio();
    }

    public function pixels(): int
    {
        return $this->inner()->pixels();
    }

    public function isSquare(): bool
    {
        return $this->inner()->isSquare();
    }

    public function isPortrait(): bool
    {
        return $this->inner()->isPortrait();
    }

    public function isLandscape(): bool
    {
        return $this->inner()->isLandscape();
    }

    public function exif(): array
    {
        return $this->inner()->exif();
    }

    public function iptc(): array
    {
        return $this->inner()->iptc();
    }

    abstract protected function inner(): Image;
}
