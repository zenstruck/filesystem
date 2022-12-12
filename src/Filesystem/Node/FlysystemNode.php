<?php

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\FilesystemOperator;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory\FlysystemDirectory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class FlysystemNode implements Node
{
    protected ?\DateTimeImmutable $lastModified = null;
    protected ?string $visibility = null;

    /**
     * @internal
     */
    public function __construct(private string $path, protected FilesystemOperator $flysystem)
    {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): string
    {
        return \pathinfo($this->path(), \PATHINFO_BASENAME);
    }

    public function directory(): ?Directory
    {
        $dirname = \pathinfo($this->path(), \PATHINFO_DIRNAME);

        return '.' === $dirname ? null : new FlysystemDirectory($dirname, $this->flysystem);
    }

    public function lastModified(): \DateTimeImmutable
    {
        return $this->lastModified ??= \DateTimeImmutable::createFromFormat('U', $this->flysystem->lastModified($this->path())) // @phpstan-ignore-line
            ->setTimezone(new \DateTimeZone(\date_default_timezone_get()))
        ;
    }

    public function visibility(): string
    {
        return $this->visibility ??= $this->flysystem->visibility($this->path());
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
}