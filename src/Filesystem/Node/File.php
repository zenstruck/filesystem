<?php

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\FileAttributes;
use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\Checksum;
use Zenstruck\Filesystem\Node\File\Size;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class File extends Node
{
    private Size $size;
    private string $mimeType;
    private Checksum $checksum;

    public function __construct(FileAttributes $attributes, Operator $operator)
    {
        parent::__construct($attributes, $operator);

        if ($size = $attributes->fileSize()) {
            $this->size = Size::binary($size);
        }

        if ($mimeType = $attributes->mimeType()) {
            $this->mimeType = $mimeType;
        }
    }

    public function size(): Size
    {
        return $this->size ??= Size::binary($this->operator->fileSize($this->path()));
    }

    public function mimeType(): string
    {
        return $this->mimeType ??= $this->operator->mimeType($this->path());
    }

    public function contents(): string
    {
        return $this->operator->read($this->path());
    }

    /**
     * @return resource
     */
    public function read()
    {
        return $this->operator->readStream($this->path());
    }

    /**
     * Calculate the checksum for the file. Defaults to md5.
     *
     * @example $file->checksum()->toString() (md5 hash of contents)
     * @example $file->checksum()->sha1()->toString() (sha1 hash of contents)
     * @example $file->checksum()->metadata()->toString() (md5 hash of file size + last modified timestamp + mime-type)
     * @example $file->checksum()->metadata()->sha1()->toString() (sha1 hash of file size + last modified timestamp + mime-type)
     */
    public function checksum(): Checksum
    {
        return $this->checksum ??= new Checksum($this, $this->operator);
    }

    /**
     * Whether the contents of this file and another match (content checksum is used).
     */
    public function contentsEquals(self $other): bool
    {
        return $this->checksum->equals($other->checksum());
    }

    /**
     * Whether the metadata of this file and another match (metadata checksum is used).
     */
    public function metadataEquals(self $other): bool
    {
        return $this->checksum->forMetadata()->equals($other->checksum());
    }

    /**
     * @return Directory<Node>
     */
    public function directory(): Directory
    {
        return new Directory($this->operator->directoryAttributesFor($this->dirname()), $this->operator);
    }

    public function extension(): ?string
    {
        return \pathinfo($this->path(), \PATHINFO_EXTENSION) ?: null;
    }

    public function refresh(): Node
    {
        unset($this->size, $this->mimeType, $this->checksum);

        return parent::refresh();
    }
}
