<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class Checksum
{
    private const MD5 = 'md5';
    private const SHA1 = 'sha1';

    private string $mode = self::MD5;
    private bool $metadata = false;
    private string $checksum;

    /**
     * @internal
     */
    public function __construct(private File $file)
    {
    }

    public function __clone(): void
    {
        unset($this->checksum);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        if (isset($this->checksum)) {
            return $this->checksum;
        }

        $contents = $this->metadata ? \sprintf('%s%s', $this->file->size()->bytes(), $this->file->lastModified()->getTimestamp()) : $this->file->contents();

        return $this->checksum = match ($this->mode) {
            self::MD5 => \md5($contents),
            self::SHA1 => \sha1($contents),
            default => throw new \LogicException('Invalid mode.'),
        };
    }

    /**
     * Run the checksum on file metadata only (size/last modified).
     */
    public function metadata(): self
    {
        $clone = clone $this;
        $clone->metadata = true;

        return $clone;
    }

    /**
     * Set the checksum algorithm to SHA1.
     */
    public function sha1(): self
    {
        $clone = clone $this;
        $clone->mode = self::SHA1;

        return $clone;
    }

    /**
     * Set the checksum algorithm to MD5.
     */
    public function md5(): self
    {
        $clone = clone $this;
        $clone->mode = self::MD5;

        return $clone;
    }
}
