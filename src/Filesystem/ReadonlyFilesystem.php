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
    use WrappedFilesystem;

    public function __construct(private Filesystem $inner)
    {
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

    protected function inner(): Filesystem
    {
        return $this->inner;
    }

    /**
     * @return no-return
     */
    private static function throw(): void
    {
        throw new \BadMethodCallException('This is a readonly filesystem, write operations not permitted.');
    }
}
