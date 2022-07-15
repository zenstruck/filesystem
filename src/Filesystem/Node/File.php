<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class File extends Node
{
    private int $size;
    private string $mimeType;

    public function size(): int
    {
        return $this->size ??= $this->flysystem->fileSize($this->path());
    }

    public function mimeType(): string
    {
        return $this->mimeType ??= $this->flysystem->mimeType($this->path());
    }

    public function contents(): string
    {
        return $this->flysystem->read($this->path());
    }

    /**
     * @return resource
     */
    public function read()
    {
        return $this->flysystem->readStream($this->path());
    }

    /**
     * @return Directory<Node>
     */
    public function directory(): Directory
    {
        return self::createDirectory($this->dirname(), $this->flysystem);
    }

    public function extension(): ?string
    {
        return \pathinfo($this->path(), \PATHINFO_EXTENSION) ?: null;
    }

    public function dirname(): string
    {
        return \pathinfo($this->path(), \PATHINFO_DIRNAME);
    }

    public function filename(): ?string
    {
        return \pathinfo($this->path(), \PATHINFO_FILENAME) ?: null;
    }

    public function basename(): ?string
    {
        return \pathinfo($this->path(), \PATHINFO_BASENAME) ?: null;
    }
}
