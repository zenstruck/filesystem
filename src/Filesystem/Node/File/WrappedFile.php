<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\WrappedNode;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait WrappedFile
{
    use WrappedNode;

    public function tempFile(): \SplFileInfo
    {
        return $this->inner()->tempFile();
    }

    public function size(): Information
    {
        return $this->inner()->size();
    }

    public function directory(): Directory
    {
        return $this->inner()->directory();
    }

    public function mimeType(): string
    {
        return $this->inner()->mimeType();
    }

    public function contents(): string
    {
        return $this->inner()->contents();
    }

    public function read()
    {
        return $this->inner()->read();
    }

    public function checksum(): Checksum
    {
        return $this->inner()->checksum();
    }

    public function contentsEquals(File $other): bool
    {
        return $this->inner()->contentsEquals($other);
    }

    public function metadataEquals(File $other): bool
    {
        return $this->inner()->metadataEquals($other);
    }

    public function extension(): ?string
    {
        return $this->inner()->extension();
    }

    public function guessExtension(): ?string
    {
        return $this->inner()->guessExtension();
    }

    public function nameWithoutExtension(): string
    {
        return $this->inner()->nameWithoutExtension();
    }

    public function url(array $options = []): Uri
    {
        return $this->inner()->url($options);
    }

    abstract protected function inner(): File;
}
