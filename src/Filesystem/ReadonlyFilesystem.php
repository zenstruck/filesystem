<?php

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ReadonlyFilesystem implements Filesystem
{
    public function __construct(private Filesystem $inner)
    {
    }

    public function node(string $path = ''): File|Directory
    {
        return $this->inner->node($path);
    }

    public function file(string $path): File
    {
        return $this->inner->file($path);
    }

    public function directory(string $path = ''): Directory
    {
        return $this->inner->directory($path);
    }

    public function exists(string $path = ''): bool
    {
        return $this->inner->exists($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        self::throw();
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        self::throw();
    }

    public function delete(Directory|string $path = '', array $config = []): static
    {
        self::throw();
    }

    public function mkdir(string $path = '', array $config = []): static
    {
        self::throw();
    }

    public function chmod(string $path, string $visibility): static
    {
        self::throw();
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        self::throw();
    }

    public function last(): File|Directory
    {
        self::throw();
    }

    /**
     * @return no-return
     */
    private static function throw(): void
    {
        throw new \BadMethodCallException('This is a readonly filesystem, write operations not permitted.');
    }
}
