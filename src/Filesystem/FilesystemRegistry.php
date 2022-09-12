<?php

namespace Zenstruck\Filesystem;

use Psr\Container\ContainerInterface;
use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FilesystemRegistry
{
    /** @var array<string,Filesystem> */
    private array $filesystems;
    private ?ContainerInterface $locator;

    /**
     * @param ContainerInterface|array<string,Filesystem> $filesystems
     */
    public function __construct(ContainerInterface|array $filesystems = [])
    {
        $this->filesystems = \is_array($filesystems) ? $filesystems : [];
        $this->locator = $filesystems instanceof ContainerInterface ? $filesystems : null;
    }

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
        if (isset($this->filesystems[$name])) {
            return $this->filesystems[$name];
        }

        if ($factory) {
            return $this->filesystems[$name] = new LazyFilesystem($factory);
        }

        if ($this->locator) {
            return $this->filesystems[$name] = new LazyFilesystem(fn() => $this->locator->get($name));
        }

        throw new \RuntimeException(\sprintf('Filesystem "%s" is not registered.', $name));
    }

    public function reset(): void
    {
        $this->filesystems = [];
    }
}
