<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\NodeExists;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFilesystem implements Filesystem
{
    public function __construct(private FilesystemOperator $flysystem)
    {
    }

    public function node(string $path = ''): File|Directory
    {
        if ($this->flysystem->fileExists($path)) {
            return new File(new FileAttributes($path), $this->flysystem);
        }

        if ($this->flysystem->directoryExists($path)) {
            return new Directory(new DirectoryAttributes($path), $this->flysystem);
        }

        throw NodeNotFound::for($path);
    }

    public function file(string $path): File
    {
        $node = $this->node($path);

        return $node instanceof File ? $node : throw NodeTypeMismatch::expectedFileAt($path);
    }

    public function directory(string $path = ''): Directory
    {
        $node = $this->node($path);

        return $node instanceof Directory ? $node : throw NodeTypeMismatch::expectedDirectoryAt($path);
    }

    public function exists(string $path = ''): bool
    {
        return $this->flysystem->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        if (($config['fail_if_exists'] ?? false) && $this->exists($destination)) {
            throw NodeExists::forCopy($source, $this->node($destination));
        }

        try {
            $this->flysystem->copy($source, $destination, $config);
        } catch (UnableToCopyFile $e) {
            if (!$this->exists($source)) {
                throw NodeNotFound::for($source);
            }

            throw $e;
        }
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        if (($config['fail_if_exists'] ?? false) && $this->exists($destination)) {
            throw NodeExists::forMove($source, $this->node($destination));
        }

        try {
            $this->flysystem->move($source, $destination, $config);
        } catch (UnableToMoveFile $e) {
            if (!$this->exists($source)) {
                throw NodeNotFound::for($source);
            }

            throw $e;
        }
    }

    public function delete(string|Directory $path = '', array $config = []): int
    {
        $config['progress'] ??= static function(Node $node) {};

        if ($path instanceof Directory) {
            $count = 0;

            foreach ($path as $node) {
                $count += $this->delete($node->path(), $config);
            }

            return $count;
        }

        try {
            $node = $this->node($path);
        } catch (NodeNotFound) {
            return 0;
        }

        $config['progress']($node);

        $node instanceof File ? $this->flysystem->delete($path) : $this->flysystem->deleteDirectory($path);

        return 1;
    }

    public function mkdir(string $path = '', array $config = []): void
    {
        $this->flysystem->createDirectory($path, $config);
    }

    public function chmod(string $path, string $visibility): void
    {
        $this->flysystem->setVisibility($path, $visibility);
    }

    public function write(string $path, mixed $value, array $config = []): File|Directory
    {
        if (($config['fail_if_exists'] ?? false) && $this->exists($path)) {
            throw NodeExists::forWrite($this->node($path));
        }

        $config['progress'] ??= static function(File $file) {};

        if (\is_callable($value)) {
            $tempFile = $value(TempFile::with($this->file($path)->read()));

            if (!$tempFile instanceof \SplFileInfo || !$tempFile->isReadable() || $tempFile->isDir()) {
                throw new \LogicException('Readable SplFileInfo (file) must be returned from callback.');
            }

            return $this->write($path, $tempFile, $config);
        }

        if (\is_string($value)) { // check if local filename
            try {
                if ((new SymfonyFilesystem())->exists($value)) {
                    $value = new \SplFileInfo($value);
                }
            } catch (IOException) {
                // value length was too long to be a filename, keep as string
            }
        }

        if ($value instanceof \SplFileInfo && $value->isDir()) { // check if local directory
            $relative = new Path($path);

            foreach (Finder::create()->in((string) $value)->files() as $file) {
                $this->write($relative->append($file->getRelativePathname()), $file, $config);
            }

            return new Directory(new DirectoryAttributes($path), $this->flysystem);
        }

        if ($value instanceof Directory) { // check if Directory node
            $relative = new Path($path);
            $prefixLength = \mb_strlen($value->path());

            foreach ($value->recursive()->files() as $file) {
                $this->write($relative->append(\mb_substr($file->path(), $prefixLength)), $file, $config);
            }

            return new Directory(new DirectoryAttributes($path), $this->flysystem);
        }

        if ($value instanceof File) { // check if File node
            $value = ResourceWrapper::wrap($value->read());
        }

        if ($value instanceof \SplFileInfo) { // check if local file
            $value = ResourceWrapper::open($value, 'r');
        }

        match (true) {
            \is_string($value) => $this->flysystem->write($path, $value, $config),
            \is_resource($value) => $this->flysystem->writeStream($path, $value, $config),
            $value instanceof ResourceWrapper => $this->flysystem->writeStream($path, $value->get(), $config),
            default => throw new \InvalidArgumentException(\sprintf('Invalid $value type: "%s".', \get_debug_type($value))),
        };

        if ($value instanceof ResourceWrapper) { // if we opened a resource, close
            $value->close();
        }

        $file = new File(new FileAttributes($path), $this->flysystem);

        $config['progress']($file);

        return $file;
    }
}
