<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public function path(): Path
    {
        return $this->inner()->path();
    }

    public function dsn(): Dsn
    {
        return $this->inner()->dsn();
    }

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

    public function ensureExists(): static
    {
        $this->inner()->ensureExists();

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
