<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory\FlysystemDirectory;
use Zenstruck\Filesystem\Node\File\FlysystemFile;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\FlysystemImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class FlysystemNode implements Node
{
    private Path $path;
    private Dsn $dsn;

    /** @var array<string,mixed> */
    protected array $cache = [];

    public function __construct(string|Path $path, protected Operator $operator)
    {
        $this->path = \is_string($path) ? new Path($path) : $path;
    }

    public function path(): Path
    {
        return $this->path;
    }

    public function dsn(): Dsn
    {
        return $this->dsn ??= Dsn::create($this->operator->name(), $this->path);
    }

    public function directory(): ?Directory
    {
        $dirname = $this->path()->dirname();

        return '.' === $dirname ? null : new FlysystemDirectory($dirname, $this->operator);
    }

    public function lastModified(): \DateTimeImmutable
    {
        return $this->cache['last-modified'] ??= \DateTimeImmutable::createFromFormat('U', $this->operator->lastModified($this->path())) // @phpstan-ignore-line
            ->setTimezone(new \DateTimeZone(\date_default_timezone_get()))
        ;
    }

    public function visibility(): string
    {
        return $this->cache['visibility'] ??= $this->operator->visibility($this->path());
    }

    public function refresh(): static
    {
        $this->cache = [];

        return $this;
    }

    public function isDirectory(): bool
    {
        return $this instanceof FlysystemDirectory;
    }

    public function isFile(): bool
    {
        return $this instanceof FlysystemFile;
    }

    public function exists(): bool
    {
        return $this->operator->{$this instanceof File ? 'fileExists' : 'directoryExists'}($this->path());
    }

    public function ensureExists(): static
    {
        if ($this->exists()) {
            return $this;
        }

        throw match (true) {
            $this instanceof File && $this->operator->directoryExists($this->path()) => NodeTypeMismatch::expectedFileAt($this->path()),
            $this instanceof Directory && $this->operator->fileExists($this->path()) => NodeTypeMismatch::expectedDirectoryAt($this->path()),
            default => new NodeNotFound($this->path(), $this->operator->name()),
        };
    }

    public function ensureFile(): File
    {
        if ($this instanceof FlysystemFile) {
            return $this;
        }

        throw NodeTypeMismatch::expectedFileAt($this->path());
    }

    public function ensureDirectory(): Directory
    {
        if ($this instanceof FlysystemDirectory) {
            return $this;
        }

        throw NodeTypeMismatch::expectedDirectoryAt($this->path());
    }

    /**
     * @return FlysystemImage
     */
    public function ensureImage(): Image
    {
        if ($this instanceof FlysystemImage) {
            return $this;
        }

        $file = $this->ensureFile();

        if (!$file->isImage()) {
            throw new NodeTypeMismatch(\sprintf('Expected file at path "%s" to be an image but is "%s".', $this->path(), $file->mimeType()));
        }

        $image = new FlysystemImage($this->path(), $this->operator);
        $image->cache = $this->cache;

        return $image;
    }
}
