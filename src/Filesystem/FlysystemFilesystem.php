<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\NodeExists;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\UnableToCopyDirectory;
use Zenstruck\Filesystem\Exception\UnableToMoveDirectory;
use Zenstruck\Filesystem\Flysystem\Adapter\LocalAdapter;
use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFilesystem implements Filesystem
{
    private Operator $operator;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(FilesystemAdapter|string $adapter, array $config = [], ?PathNormalizer $pathNormalizer = null)
    {
        if (\is_string($adapter)) {
            $adapter = new LocalAdapter($adapter);
        }

        $this->operator = new Operator($adapter, $config, $pathNormalizer);
    }

    public function node(string $path = ''): File|Directory
    {
        if ($this->operator->fileExists($path)) {
            return new File($this->operator->fileAttributesFor($path), $this->operator);
        }

        if ($this->operator->directoryExists($path)) {
            return new Directory($this->operator->directoryAttributesFor($path), $this->operator);
        }

        throw NodeNotFound::for($path);
    }

    public function file(string $path): File
    {
        return $this->node($path)->ensureFile();
    }

    public function directory(string $path = ''): Directory
    {
        return $this->node($path)->ensureDirectory();
    }

    public function exists(string $path = ''): bool
    {
        return $this->operator->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        if (($config['fail_if_exists'] ?? false) && $this->exists($destination)) {
            throw NodeExists::forCopy($source, $this->node($destination));
        }

        unset($config['fail_if_exists']);

        $sourceNode = $this->node($source);

        try {
            $destinationNode = $this->node($destination);
        } catch (NodeNotFound) {
            $destinationNode = null;
        }

        if ($sourceNode instanceof File && $destinationNode instanceof Directory) {
            throw UnableToCopyFile::fromLocationTo($source, $destination);
        }

        if ($sourceNode instanceof Directory && $destinationNode instanceof File) {
            throw UnableToCopyDirectory::fromLocationTo($source, $destination, 'Source is a directory but destination is a file.');
        }

        $this->delete($destination);

        if ($sourceNode instanceof Directory) {
            $this->write($destination, $sourceNode, $config);

            return;
        }

        $this->operator->copy($source, $destination, $config);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        if (($config['fail_if_exists'] ?? false) && $this->exists($destination)) {
            throw NodeExists::forMove($source, $this->node($destination));
        }

        unset($config['fail_if_exists']);

        $sourceNode = $this->node($source);

        try {
            $destinationNode = $this->node($destination);
        } catch (NodeNotFound) {
            $destinationNode = null;
        }

        if ($sourceNode instanceof File && $destinationNode instanceof Directory) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }

        if ($sourceNode instanceof Directory && $destinationNode instanceof File) {
            throw UnableToMoveDirectory::fromLocationTo($source, $destination, 'Source is a directory but destination is a file.');
        }

        $this->delete($destination);

        if ($sourceNode instanceof Directory) {
            $this->write($destination, $sourceNode, $config);
            $this->delete($source);

            return;
        }

        try {
            $this->operator->move($source, $destination, $config);
        } catch (UnableToMoveFile $e) {
            if (!$this->exists($source)) {
                throw NodeNotFound::for($source);
            }

            throw $e;
        }
    }

    public function delete(string $path = '', array $config = []): void
    {
        try {
            $node = $this->node($path);
        } catch (NodeNotFound) {
            return;
        }

        $node instanceof File ? $this->operator->delete($path) : $this->operator->deleteDirectory($path);
    }

    public function mkdir(string $path = '', array $config = []): void
    {
        $this->operator->createDirectory($path, $config);
    }

    public function chmod(string $path, string $visibility): void
    {
        $this->operator->setVisibility($path, $visibility);
    }

    public function write(string $path, mixed $value, array $config = []): File|Directory
    {
        if (($config['fail_if_exists'] ?? false) && $this->exists($path)) {
            throw NodeExists::forWrite($this->node($path));
        }

        $config['progress'] ??= static function(File $file) {};

        if (\is_callable($value)) {
            return $this->write($path, $this->operator->modifyFile($this->file($path), $value), $config);
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

            return new Directory($this->operator->directoryAttributesFor($path), $this->operator);
        }

        if ($value instanceof Directory) { // check if Directory node
            $relative = new Path($path);
            $prefixLength = \mb_strlen($value->path());

            foreach ($value->recursive()->files() as $file) {
                $this->write($relative->append(\mb_substr($file->path(), $prefixLength)), $file, $config);
            }

            return new Directory($this->operator->directoryAttributesFor($path), $this->operator);
        }

        if ($value instanceof File) { // check if File node
            $value = ResourceWrapper::wrap($value->read());
        }

        if ($value instanceof \SplFileInfo) { // check if local file
            $value = ResourceWrapper::open($value, 'r');
        }

        match (true) {
            \is_string($value) => $this->operator->write($path, $value, $config),
            \is_resource($value) => $this->operator->writeStream($path, $value, $config),
            $value instanceof ResourceWrapper => $this->operator->writeStream($path, $value->get(), $config),
            default => throw new \InvalidArgumentException(\sprintf('Invalid $value type: "%s".', \get_debug_type($value))),
        };

        if ($value instanceof ResourceWrapper) { // if we opened a resource, close
            $value->close();
        }

        $file = new File($this->operator->fileAttributesFor($path), $this->operator);

        $config['progress']($file);

        return $file;
    }
}
