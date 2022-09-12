<?php

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FilesystemRegistry
{
    /** @var array<string,Filesystem> */
    private array $filesystems = [];

    public function set(Filesystem $filesystem, ?string $name = null): void
    {
        $this->filesystems[$name ?? $filesystem->name()] = $filesystem;
    }

    /**
     * @param null|callable():Filesystem $factory If not found, create with this callback
     *
     * @throws \RuntimeException If the filesystem was not found and a factory was not provided
     */
    public function get(string $name, ?callable $factory = null): Filesystem
    {
        return $this->filesystems[$name] ??= $factory ? $factory() : throw new \RuntimeException(\sprintf('Filesystem "%s" is not registered.', $name));
    }

    public function reset(): void
    {
        $this->filesystems = [];
    }
}
