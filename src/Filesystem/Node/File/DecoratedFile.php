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
use Zenstruck\Stream;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait DecoratedFile
{
    public function guessExtension(): ?string
    {
        return $this->inner()->guessExtension();
    }

    public function mimeType(): string
    {
        return $this->inner()->mimeType();
    }

    public function size(): int
    {
        return $this->inner()->size();
    }

    public function contents(): string
    {
        return $this->inner()->contents();
    }

    public function read(): Stream
    {
        return $this->inner()->read();
    }

    public function checksum(?string $algo = null): string
    {
        return $this->inner()->checksum($algo);
    }

    public function publicUrl(array $config = []): string
    {
        return $this->inner()->publicUrl($config);
    }

    public function temporaryUrl(\DateTimeInterface|string $expires, array $config = []): string
    {
        return $this->inner()->temporaryUrl($expires, $config);
    }

    public function tempFile(): \SplFileInfo
    {
        return $this->inner()->tempFile();
    }

    abstract protected function inner(): File;
}
