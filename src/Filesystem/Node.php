<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\FilesystemOperator;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Node
{
    private \DateTimeImmutable $lastModified;
    private string $visibility;

    private function __construct(private string $path, protected FilesystemOperator $flysystem)
    {
    }

    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @internal
     */
    public static function createFile(string $path, FilesystemOperator $flysystem): File
    {
        return new File($path, $flysystem);
    }

    /**
     * @internal
     *
     * @return Directory<Node>
     */
    public static function createDirectory(string $path, FilesystemOperator $flysystem): Directory
    {
        return new Directory($path, $flysystem);
    }

    final public function path(): string
    {
        return $this->path;
    }

    final public function lastModified(): \DateTimeImmutable
    {
        // @phpstan-ignore-next-line
        return $this->lastModified ??= \DateTimeImmutable::createFromFormat('U', (string) $this->flysystem->lastModified($this->path()))
            // timestamp is always in UTC so convert to current system timezone
            ->setTimezone(new \DateTimeZone(\date_default_timezone_get()))
        ;
    }

    public function visibility(): string
    {
        return $this->visibility ??= $this->flysystem->visibility($this->path());
    }

    final public function isDirectory(): bool
    {
        return $this instanceof Directory;
    }

    final public function isFile(): bool
    {
        return $this instanceof File;
    }

    /**
     * @return Directory<Node>
     */
    final public function ensureDirectory(): Directory
    {
        return $this instanceof Directory ? $this : throw new \RuntimeException('Not a directory.'); // TODO add path
    }

    final public function ensureFile(): File
    {
        return $this instanceof File ? $this : throw new \RuntimeException('Not a file.'); // TODO add path
    }
}
