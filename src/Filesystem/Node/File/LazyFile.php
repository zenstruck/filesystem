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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class LazyFile extends LazyNode implements File
{
    use DecoratedFile;

    public function size(): int
    {
        return $this->attributes[__FUNCTION__] ?? $this->inner()->size();
    }

    public function checksum(?string $algo = null): string
    {
        if (null === $algo && \is_string($this->attributes[__FUNCTION__] ?? null)) {
            return $this->attributes[__FUNCTION__];
        }

        if ($algo && isset($this->attributes[__FUNCTION__][$algo])) {
            return $this->attributes[__FUNCTION__][$algo];
        }

        return $this->inner()->checksum($algo);
    }

    public function publicUrl(array $config = []): string
    {
        if (!$config && isset($this->attributes[__FUNCTION__])) {
            return $this->attributes[__FUNCTION__];
        }

        return $this->inner()->publicUrl($config);
    }

    public function guessExtension(): ?string
    {
        return $this->path()->extension() ?? $this->inner()->guessExtension();
    }

    protected function inner(): File
    {
        return $this->inner ??= $this->filesystem()->file($this->path()); // @phpstan-ignore-line
    }
}
