<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\UnregisteredFilesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Dsn;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MultiFilesystem implements Filesystem
{
    private ?Filesystem $last = null;

    public function __construct(private array|ContainerInterface $filesystems, private ?string $default = null)
    {
    }

    public function name(?string $filesystem = null): string
    {
        return $this->get($filesystem)->name();
    }

    public function node(string $path): File|Directory
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

    public function image(string $path): Image
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->image($path);
    }

    public function has(string $path): bool
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->has($path);
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

        $destFilesystem->write($destPath, $sourceFilesystem->file($sourcePath), $config);

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

        $destFilesystem->write($destPath, $sourceFilesystem->file($sourcePath), $config);
        $sourceFilesystem->delete($sourcePath);

        return $this;
    }

    public function delete(Directory|string $path, array $config = []): static
    {
        [$filesystem, $pathString] = $this->parsePath($path instanceof Directory ? $path->path() : $path);

        $filesystem->delete($path instanceof Directory ? $path : $pathString, $config);

        return $this;
    }

    public function mkdir(string $path, array $config = []): static
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
        if ($name) {
            return $this->get($name)->last();
        }

        return $this->last?->last() ?? $this->get()->last();
    }

    private function get(?string $name = null): Filesystem
    {
        $name ??= $this->default;

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

        throw new UnregisteredFilesystem($name, $e ?? null);
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

            if (!$nested instanceof self) {
                continue;
            }

            try {
                return $nested->get($key);
            } catch (UnregisteredFilesystem) {
                continue;
            }
        }

        return null;
    }

    /**
     * @return array{0:Filesystem,1:string}
     */
    private function parsePath(string $path): array
    {
        $parts = Dsn::normalize($path);

        return [$this->last = $this->get($parts[0]), $parts[1]];
    }
}
