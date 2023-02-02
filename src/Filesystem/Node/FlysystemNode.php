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

use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory\FlysystemDirectory;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\FlysystemImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class FlysystemNode implements Node
{
    private const IMAGE_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png', 'svg', 'apng', 'avif', 'jfif', 'pjpeg', 'pjp', 'webp'];

    private ?\DateTimeImmutable $lastModified = null;
    private ?string $visibility = null;
    private Path $path;
    private Dsn $dsn;

    /**
     * @internal
     */
    public function __construct(string $path, protected Operator $operator)
    {
        $this->path = new Path($path);
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

    public function ensureFile(): File
    {
        return $this instanceof File ? $this : throw NodeTypeMismatch::expectedFileAt($this->path());
    }

    public function ensureDirectory(): Directory
    {
        return $this instanceof Directory ? $this : throw NodeTypeMismatch::expectedDirectoryAt($this->path());
    }

    /**
     * @return FlysystemImage
     */
    public function ensureImage(): Image
    {
        if ($this instanceof FlysystemImage) {
            return $this;
        }

        if (!\in_array($this->ensureFile()->guessExtension(), self::IMAGE_EXTENSIONS, true)) {
            throw new NodeTypeMismatch(\sprintf('Expected file at path "%s" to be an image but is "%s".', $this->path(), $this->mimeType()));
        }

        $image = new FlysystemImage($this->path(), $this->operator);
        $image->lastModified = $this->lastModified;
        $image->visibility = $this->visibility;

        return $image;
    }
}
