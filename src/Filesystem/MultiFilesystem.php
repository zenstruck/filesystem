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
    private FilesystemRegistry $filesystems;

    public function __construct(array|ContainerInterface|FilesystemRegistry $filesystems, private ?string $default = null)
    {
        $this->filesystems = $filesystems instanceof FilesystemRegistry ? $filesystems : new FilesystemRegistry($filesystems);
    }

    public function name(?string $filesystem = null): string
    {
        return $this->get($filesystem)->name();
    }

    public function node(string $path): Node
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

    public function copy(string $source, string $destination, array $config = []): File
    {
        [$sourceFilesystem, $sourcePath] = $this->parsePath($source);
        [$destFilesystem, $destPath] = $this->parsePath($destination);

        if ($sourceFilesystem === $destFilesystem) {
            // same filesystem
            return $sourceFilesystem->copy($sourcePath, $destPath, $config);
        }

        return $destFilesystem->write($destPath, $sourceFilesystem->file($sourcePath), $config);
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        [$sourceFilesystem, $sourcePath] = $this->parsePath($source);
        [$destFilesystem, $destPath] = $this->parsePath($destination);

        if ($sourceFilesystem === $destFilesystem) {
            // same filesystem
            return $sourceFilesystem->move($sourcePath, $destPath, $config);
        }

        try {
            return $destFilesystem->write($destPath, $sourceFilesystem->file($sourcePath), $config);
        } finally {
            $sourceFilesystem->delete($sourcePath);
        }
    }

    public function delete(string $path, array $config = []): self
    {
        [$filesystem, $path] = $this->parsePath($path);

        $filesystem->delete($path, $config);

        return $this;
    }

    public function mkdir(string $path, Directory|\SplFileInfo|null $content = null, array $config = []): Directory
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->mkdir($path, $content, $config);
    }

    public function chmod(string $path, string $visibility): Node
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->chmod($path, $visibility);
    }

    public function write(string $path, mixed $value, array $config = []): File
    {
        [$filesystem, $path] = $this->parsePath($path);

        return $filesystem->write($path, $value, $config);
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

        try {
            return $this->filesystems->get($name);
        } catch (UnregisteredFilesystem $e) {
            if ($nested = $this->getFromNested($name)) {
                return $nested;
            }

            throw $e;
        }
    }

    private function getFromNested(?string $key): ?Filesystem
    {
        try {
            $names = $this->filesystems->names();
        } catch (\LogicException) {
            return null;
        }

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

        return [$this->get($parts[0]), $parts[1]];
    }
}
