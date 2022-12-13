<?php

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

    public function temporaryUrl(\DateTimeInterface $expiresAt, array $config = []): string
    {
        return $this->inner()->temporaryUrl($expiresAt, $config);
    }

    public function tempFile(): \SplFileInfo
    {
        return $this->inner()->tempFile();
    }

    abstract protected function inner(): File;
}
