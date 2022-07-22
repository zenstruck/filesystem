<?php

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\FileAttributes;
use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\Checksum;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class File extends Node
{
    private Information $size;
    private string $mimeType;
    private Checksum $checksum;

    /**
     * @internal
     */
    public function __construct(FileAttributes $attributes, Operator $operator)
    {
        parent::__construct($attributes, $operator);

        if ($size = $attributes->fileSize()) {
            $this->size = Information::binary($size);
        }

        if ($mimeType = $attributes->mimeType()) {
            $this->mimeType = $mimeType;
        }
    }

    final public function size(): Information
    {
        return $this->size ??= Information::binary($this->operator()->fileSize($this->path()));
    }

    final public function mimeType(): string
    {
        return $this->mimeType ??= $this->operator()->mimeType($this->path());
    }

    final public function contents(): string
    {
        return $this->operator()->read($this->path());
    }

    /**
     * @return resource
     */
    final public function read()
    {
        return $this->operator()->readStream($this->path());
    }

    /**
     * Calculate the checksum for the file. Defaults to md5.
     *
     * @example $file->checksum()->toString() (md5 hash of contents)
     * @example $file->checksum()->sha1()->toString() (sha1 hash of contents)
     * @example $file->checksum()->metadata()->toString() (md5 hash of file size + last modified timestamp + mime-type)
     * @example $file->checksum()->metadata()->sha1()->toString() (sha1 hash of file size + last modified timestamp + mime-type)
     */
    final public function checksum(): Checksum
    {
        return $this->checksum ??= new Checksum($this, $this->operator());
    }

    /**
     * Whether the contents of this file and another match (content checksum is used).
     */
    final public function contentsEquals(self $other): bool
    {
        return $this->checksum->equals($other->checksum());
    }

    /**
     * Whether the metadata of this file and another match (metadata checksum is used).
     */
    final public function metadataEquals(self $other): bool
    {
        return $this->checksum->forMetadata()->equals($other->checksum());
    }

    /**
     * @return Directory<Node>
     */
    final public function directory(): Directory
    {
        return new Directory($this->operator()->directoryAttributesFor($this->dirname()), $this->operator());
    }

    final public function extension(): ?string
    {
        return \pathinfo($this->path(), \PATHINFO_EXTENSION) ?: null;
    }

    /**
     * @example If $path is "foo/bar/baz.txt", returns "baz"
     * @example If $path is "foo/bar/baz", returns "baz"
     */
    final public function nameWithoutExtension(): string
    {
        return \pathinfo($this->path(), \PATHINFO_FILENAME);
    }

    /**
     * @throws UnsupportedFeature If your adapter does not support {@see FileUrl}
     */
    final public function url(): Uri
    {
        return $this->operator()->urlFor($this);
    }

    public function refresh(): static
    {
        unset($this->size, $this->mimeType, $this->checksum);

        return parent::refresh();
    }

    protected function castTo(Node $to): self
    {
        $to = parent::castTo($to);

        \assert($to instanceof self);

        if (isset($to->size)) {
            $to->size = $this->size;
        }

        if (isset($to->mimeType)) {
            $to->mimeType = $this->mimeType;
        }

        if (isset($to->checksum)) {
            $to->checksum = $this->checksum;
        }

        return $to;
    }
}
