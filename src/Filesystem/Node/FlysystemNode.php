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
 */
class FlysystemNode implements Node
{
    private const IMAGE_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png', 'svg', 'apng', 'avif', 'jfif', 'pjpeg', 'pjp', 'webp'];

    private ?\DateTimeImmutable $lastModified = null;
    private ?string $visibility = null;
    private Path $path;
    private Dsn $dsn;

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
        return $this->lastModified ??= \DateTimeImmutable::createFromFormat('U', $this->operator->lastModified($this->path())) // @phpstan-ignore-line
            ->setTimezone(new \DateTimeZone(\date_default_timezone_get()))
        ;
    }

    public function visibility(): string
    {
        return $this->visibility ??= $this->operator->visibility($this->path());
    }

    public function refresh(): static
    {
        $this->lastModified = $this->visibility = null;

        return $this;
    }

    public function exists(): bool
    {
        return $this->operator->has($this->path());
    }

    public function ensureExists(): static
    {
        if (!$this->exists()) {
            throw new NodeNotFound($this->path());
        }

        return $this;
    }

    public function ensureFile(): File
    {
        if ($this instanceof FlysystemFile) {
            return $this;
        }

        if ($this->operator->fileExists($this->path())) {
            $file = new FlysystemFile($this->path(), $this->operator);
            $file->lastModified = $this->lastModified;
            $file->visibility = $this->visibility;

            return $file;
        }

        throw NodeTypeMismatch::expectedFileAt($this->path());
    }

    public function ensureDirectory(): Directory
    {
        if ($this instanceof FlysystemDirectory) {
            return $this;
        }

        if ($this->operator->directoryExists($this->path())) {
            $directory = new FlysystemDirectory($this->path(), $this->operator);
            $directory->lastModified = $this->lastModified;
            $directory->visibility = $this->visibility;

            return $directory;
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

        if (!\in_array($file->guessExtension(), self::IMAGE_EXTENSIONS, true)) {
            throw new NodeTypeMismatch(\sprintf('Expected file at path "%s" to be an image but is "%s".', $this->path(), $file->mimeType()));
        }

        $image = new FlysystemImage($this->path(), $this->operator);
        $image->lastModified = $this->lastModified;
        $image->visibility = $this->visibility;

        return $image;
    }
}
