<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Finder\Finder;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\LocalAdapter;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\Exception\NodeExists;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\UnableToCopyDirectory;
use Zenstruck\Filesystem\Exception\UnableToMoveDirectory;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-type GlobalOptions = array{
 *     url_prefix?: string,
 *     url_prefixes?: array,
 *     path_normalizer?: PathNormalizer
 * }
 */
final class AdapterFilesystem implements Filesystem
{
    private Operator $operator;
    private string|\LogicException $last;

    /**
     * @param GlobalOptions|array<string,mixed> $config
     */
    public function __construct(FilesystemAdapter|string $adapter, array $config = [])
    {
        if (\is_string($adapter)) {
            $adapter = new LocalAdapter($adapter);
        }

        $this->operator = new Operator($adapter, $config);
        $this->last = new \LogicException('No operations have been performed.');
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

    public function last(): File|Directory
    {
        return \is_string($this->last) ? $this->node($this->last) : throw $this->last;
    }

    public function exists(string $path = ''): bool
    {
        return $this->operator->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
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

            return $this;
        }

        $this->operator->copy($source, $destination, $config);
        $this->last = $destination;

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
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
            $this->last = $destination; // because delete() disables last

            return $this;
        }

        $this->operator->move($source, $destination, $config);
        $this->last = $destination;

        return $this;
    }

    public function delete(string|Directory $path = '', array $config = []): static
    {
        if ($path instanceof Directory) {
            foreach ($path as $node) {
                $this->delete($node->path(), $config);
            }
        }

        try {
            $node = $this->node($path);
        } catch (NodeNotFound) {
            $node = null;
        }

        match (true) {
            $node instanceof File => $this->operator->delete($path),
            $node instanceof Directory => $this->operator->deleteDirectory($path),
            default => null,
        };

        if ($node && isset($config['progress'])) {
            $config['progress']($node);
        }

        $this->last = new \LogicException('Last node not available as the last operation deleted it.');

        return $this;
    }

    public function mkdir(string $path = '', array $config = []): static
    {
        try {
            if ($this->node($path)->isFile()) {
                throw UnableToCreateDirectory::atLocation($path, 'Location is a file.');
            }
        } catch (NodeNotFound) {
        }

        $this->operator->createDirectory($path, $config);
        $this->last = $path;

        return $this;
    }

    public function chmod(string $path, string $visibility): static
    {
        $this->operator->setVisibility($path, $visibility);
        $this->last = $path;

        return $this;
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        try {
            $node = $this->node($path);
        } catch (NodeNotFound) {
            $node = null;
        }

        if ($node && ($config['fail_if_exists'] ?? false)) {
            throw NodeExists::forWrite($node);
        }

        if ($node instanceof Directory) {
            throw UnableToWriteFile::atLocation($path, 'Location is a directory.');
        }

        if (\is_callable($value)) {
            $file = $this->operator->realFile($this->file($path));

            if (!$file->isReadable() || $file->isDir()) {
                throw new \LogicException(\sprintf('File "%s" is not a readable file.', $file));
            }

            $file = $value($file);

            if (!$file instanceof \SplFileInfo || !$file->isReadable() || $file->isDir()) {
                throw new \LogicException('Readable SplFileInfo (file) must be returned from callback.');
            }

            return $this->write($path, $file, $config);
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
                $this->write(
                    $relative->append($file->getRelativePathname()),
                    $file,
                    \array_merge($config, ['set_last_path' => false])
                );
            }

            $this->last = $path;

            return $this;
        }

        if ($value instanceof Directory) { // check if Directory node
            $relative = new Path($path);
            $prefixLength = \mb_strlen($value->path());

            foreach ($value->recursive()->files() as $file) {
                $this->write(
                    $relative->append(\mb_substr($file->path(), $prefixLength)),
                    $file,
                    \array_merge($config, ['set_last_path' => false])
                );
            }

            $this->last = $path;

            return $this;
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

        if (isset($config['progress'])) {
            $config['progress'](new File($this->operator->fileAttributesFor($path), $this->operator));
        }

        if ($config['set_last_path'] ?? true) {
            $this->last = $path;
        }

        return $this;
    }
}
