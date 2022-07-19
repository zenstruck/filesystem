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

    private const SIZE = 'size';
    private const LAST_MODIFIED = 'last_modified';
    private const MIME_TYPE = 'mime_type';

    /** @var string[] */
    private array $metadata = [];
    private string $checksum;
    private string $mode = self::MD5;

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

    public function equals(self $other): bool
    {
        $other = clone $other;
        $other->metadata = $this->metadata;
        $other->mode = $this->mode;

        return $other->toString() === $this->toString();
    }

    public function toString(): string
    {
        if (isset($this->checksum)) {
            return $this->checksum;
        }

        $contents = $this->metadata ? $this->metadataString() : $this->file->contents();

        return $this->checksum = match ($this->mode) {
            self::MD5 => \md5($contents),
            self::SHA1 => \sha1($contents),
            default => throw new \LogicException('Invalid mode.'),
        };
    }

    /**
     * Calculate the checksum for file metadata only (size/last modified/mime-type).
     */
    public function forMetadata(): self
    {
        $clone = clone $this;
        $clone->metadata = [self::SIZE, self::LAST_MODIFIED, self::MIME_TYPE];

        return $clone;
    }

    /**
     * Calculate the checksum for file content.
     */
    public function forContent(): self
    {
        $clone = clone $this;
        $clone->metadata = [];

        return $clone;
    }

    /**
     * Calculate the checksum for file metadata only (size).
     * Can chain with {@see forMimeType()} and {@see forLastModified()}.
     */
    public function forSize(): self
    {
        $clone = clone $this;
        $clone->metadata[] = self::SIZE;

        return $clone;
    }

    /**
     * Calculate the checksum for file metadata only (last modified).
     * Can chain with {@see forMimeType()} and {@see forSize()}.
     */
    public function forLastModified(): self
    {
        $clone = clone $this;
        $clone->metadata[] = self::LAST_MODIFIED;

        return $clone;
    }

    /**
     * Calculate the checksum for file metadata only (last modified).
     * Can chain with {@see forLastModified()} and {@see forSize()}.
     */
    public function forMimeType(): self
    {
        $clone = clone $this;
        $clone->metadata[] = self::MIME_TYPE;

        return $clone;
    }

    /**
     * Set the checksum algorithm to SHA1.
     */
    public function useSha1(): self
    {
        $clone = clone $this;
        $clone->mode = self::SHA1;

        return $clone;
    }

    /**
     * Set the checksum algorithm to MD5.
     */
    public function useMd5(): self
    {
        $clone = clone $this;
        $clone->mode = self::MD5;

        return $clone;
    }

    private function metadataString(): string
    {
        $metadata = \array_unique($this->metadata);
        $ret = '';

        \sort($metadata);

        foreach ($metadata as $type) {
            $ret .= match ($type) {
                self::SIZE => $this->file->size()->bytes(),
                self::LAST_MODIFIED => $this->file->lastModified()->getTimestamp(),
                self::MIME_TYPE => $this->file->mimeType(),
                default => throw new \LogicException('Unexpected type.'),
            };
        }

        return $ret;
    }
}
