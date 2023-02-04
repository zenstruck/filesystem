<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\LazyNode;
use Zenstruck\Filesystem\Node\Metadata;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
class LazyFile extends LazyNode implements File
{
    use DecoratedFile;

    public function mimeType(): string
    {
        return $this->attributes[Metadata::MIME_TYPE] ?? $this->inner()->mimeType();
    }

    public function size(): int
    {
        return $this->attributes[Metadata::SIZE] ?? $this->inner()->size();
    }

    public function checksum(?string $algo = null): string
    {
        if (null === $algo && \is_string($this->attributes[Metadata::CHECKSUM] ?? null)) {
            return $this->attributes[Metadata::CHECKSUM];
        }

        if ($algo && isset($this->attributes[Metadata::CHECKSUM][$algo])) {
            return $this->attributes[Metadata::CHECKSUM][$algo];
        }

        return $this->inner()->checksum($algo);
    }

    public function publicUrl(array $config = []): string
    {
        if (!$config && isset($this->attributes[Metadata::PUBLIC_URL])) {
            return $this->attributes[Metadata::PUBLIC_URL];
        }

        return $this->inner()->publicUrl($config);
    }

    public function guessExtension(): ?string
    {
        return $this->path()->extension() ?? $this->inner()->guessExtension();
    }

    public function ensureFile(): static
    {
        return $this;
    }

    protected function inner(): File
    {
        return $this->inner ??= $this->filesystem()->file($this->path()); // @phpstan-ignore-line
    }
}
