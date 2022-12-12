<?php

namespace Zenstruck\Filesystem;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\UnregisteredFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FilesystemRegistry
{
    /** @var array<string,Filesystem> */
    private array $cache = [];

    /**
     * @param ContainerInterface|array<string,Filesystem> $filesystems
     */
    public function __construct(private ContainerInterface|array $filesystems = [])
    {
    }

    /**
     * @throws UnregisteredFilesystem
     */
    public function get(string $name): Filesystem
    {
        return $this->cache[$name] ??= new LazyFilesystem(fn() => $this->resolveFilesystem($name));
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    private function resolveFilesystem(string $name): Filesystem
    {
        if ($this->filesystems instanceof ContainerInterface) {
            try {
                return $this->filesystems->get($name);
            } catch (NotFoundExceptionInterface $e) {
                throw new UnregisteredFilesystem($name, $e);
            }
        }

        return $this->filesystems[$name] ?? throw new UnregisteredFilesystem($name);
    }
}
