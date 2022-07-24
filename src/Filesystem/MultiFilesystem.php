<?php

namespace Zenstruck\Filesystem;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MultiFilesystem implements Filesystem
{
    /**
     * @param array<string,Filesystem>|ContainerInterface $filesystems
     * @param string|null                                 $default     Default filesystem to use if no scheme provided
     */
    public function __construct(private array|ContainerInterface $filesystems, private ?string $default = null)
    {
    }

    public function get(?string $name = null): Filesystem
    {
        $name = $name ?? $this->default;

        if (null === $name && $nested = $this->getFromNested($name)) {
            return $nested;
        }

        if (null === $name) {
            throw new \LogicException('Default filesystem name not set.');
        }

        if (\is_array($this->filesystems) && \array_key_exists($name, $this->filesystems)) {
            return $this->filesystems[$name];
        }

        if ($this->filesystems instanceof ContainerInterface) {
            try {
                return $this->filesystems->get($name);
            } catch (NotFoundExceptionInterface $e) {
            }
        }

        if ($nested = $this->getFromNested($name)) {
            return $nested;
        }

        throw new \InvalidArgumentException(\sprintf('Filesystem "%s" not found.', $name), previous: $e ?? null);
    }

    public function name(?string $name = null): string
    {
        return $this->get($name)->name();
    }

    public function node(string $path = ''): File|Directory
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->node($path);
    }

    public function file(string $path): File
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->file($path);
    }

    public function directory(string $path = ''): Directory
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->directory($path);
    }

    public function exists(string $path = ''): bool
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->exists($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        [$sourceFilesystem, $sourcePath] = $this->parsePath($source);
        [$destFilesystem, $destPath] = $this->parsePath($destination);

        if ($sourceFilesystem === $destFilesystem) {
            // same filesystem
            $sourceFilesystem->copy($sourcePath, $destPath, $config);

            return $this;
        }

        $destFilesystem->write($destPath, $sourceFilesystem->node($sourcePath), $config);

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        [$sourceFilesystem, $sourcePath] = $this->parsePath($source);
        [$destFilesystem, $destPath] = $this->parsePath($destination);

        if ($sourceFilesystem === $destFilesystem) {
            // same filesystem
            $sourceFilesystem->move($sourcePath, $destPath, $config);

            return $this;
        }

        $destFilesystem->write($destPath, $sourceFilesystem->node($sourcePath), $config);
        $sourceFilesystem->delete($sourcePath);

        return $this;
    }

    public function delete(Directory|string $path = '', array $config = []): static
    {
        [$filesystem, $path] = $this->parsePath($path);

        $filesystem->delete($path, $config);

        return $this;
    }

    public function mkdir(string $path = '', array $config = []): static
    {
        [$filesystem, $path] = $this->parsePath($path);

        $filesystem->mkdir($path, $config);

        return $this;
    }

    public function chmod(string $path, string $visibility): static
    {
        [$filesystem, $path] = $this->parsePath($path);

        $filesystem->chmod($path, $visibility);

        return $this;
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        [$filesystem, $path] = $this->parsePath($path);

        $filesystem->write($path, $value, $config);

        return $this;
    }

    public function last(?string $name = null): File|Directory
    {
        return $this->get($name)->last();
    }

    private function getFromNested(?string $key): ?Filesystem
    {
        $names = \array_keys(match (true) {
            \is_array($this->filesystems) => $this->filesystems,
            $this->filesystems instanceof ServiceProviderInterface => $this->filesystems->getProvidedServices(),
            default => [],
        });

        foreach ($names as $name) {
            $nested = $this->get($name);

            if ($nested instanceof self) {
                return $this->get($key);
            }
        }

        return null;
    }

    /**
     * @return array{0:Filesystem,1:string}
     */
    private function parsePath(string $path): array
    {
        $parts = \explode('://', $path, 2);

        if (2 !== \count($parts)) {
            return [$this->get(), $path];
        }

        return [$this->get($parts[0]), $parts[1]];
    }
}
