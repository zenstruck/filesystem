<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait DecoratedNode
{
    public function directory(): ?Directory
    {
        return $this->inner()->directory();
    }

    public function lastModified(): \DateTimeImmutable
    {
        return $this->inner()->lastModified();
    }

    public function visibility(): string
    {
        return $this->inner()->visibility();
    }

    public function exists(): bool
    {
        return $this->inner()->exists();
    }

    public function mimeType(): string
    {
        return $this->inner()->mimeType();
    }

    public function refresh(): static
    {
        $this->inner()->refresh();

        return $this;
    }

    public function ensureFile(): File
    {
        return $this->inner()->ensureFile();
    }

    public function ensureDirectory(): Directory
    {
        return $this->inner()->ensureDirectory();
    }

    public function ensureImage(): Image
    {
        return $this->inner()->ensureImage();
    }

    abstract protected function inner(): Node;
}
