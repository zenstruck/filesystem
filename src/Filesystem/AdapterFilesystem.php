<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;
use Psr\Container\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\LocalAdapter;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\Exception\NodeExists;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Exception\UnableToCopyDirectory;
use Zenstruck\Filesystem\Exception\UnableToMoveDirectory;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Uri\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-type GlobalConfig = array{
 *     name?: string,
 *     path_normalizer?: PathNormalizer,
 *     image_check_mime?: bool,
 * }
 * @phpstan-type Features = ContainerInterface|object[]
 */
final class AdapterFilesystem implements Filesystem
{
    private Operator $operator;
    private string $name;
    private string|\LogicException $last;

    /**
     * @param GlobalConfig|array<string,mixed> $config
     * @param Features                         $features
     */
    public function __construct(FilesystemAdapter|string $adapter, private array $config = [], iterable|ContainerInterface $features = [])
    {
        if (\is_string($adapter)) {
            $adapter = new LocalAdapter($adapter);
        }

        $this->name = $this->config['name'] ?? 'default';
        $this->operator = new Operator($adapter, $this->name, $config, $features);
        $this->last = new \LogicException('No operations have been performed.');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function node(string $path): File|Directory
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
        if ($this->operator->fileExists($path)) {
            return new File($this->operator->fileAttributesFor($path), $this->operator);
        }

        if ($this->has($path)) {
            throw NodeTypeMismatch::expectedFileAt($path);
        }

        throw NodeNotFound::for($path);
    }

    public function image(string $path, array $config = []): Image
    {
        if (isset($this->config['image_check_mime'])) {
            $config = \array_merge(['check_mime' => $this->config['image_check_mime']], $config);
        }

        return $this->file($path)->ensureImage($config);
    }

    public function directory(string $path): Directory
    {
        if ($this->operator->directoryExists($path)) {
            return new Directory($this->operator->directoryAttributesFor($path), $this->operator);
        }

        if ($this->has($path)) {
            throw NodeTypeMismatch::expectedDirectoryAt($path);
        }

        throw NodeNotFound::for($path);
    }

    public function last(): File|Directory
    {
        return \is_string($this->last) ? $this->node($this->last) : throw $this->last;
    }

    public function has(string $path): bool
    {
        return $this->operator->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        if (($config['fail_if_exists'] ?? false) && $this->has($destination)) {
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
            return $this->write($destination, $sourceNode->recursive(), $config);
        }

        $this->operator->copy($source, $destination, $config);
        $this->last = $destination;

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        if (($config['fail_if_exists'] ?? false) && $this->has($destination)) {
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

    public function delete(string|Directory $path, array $config = []): static
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

    public function mkdir(string $path, array $config = []): static
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

            foreach ($value->files() as $file) {
                $this->write(
                    $relative->append(\mb_substr($file->path(), $prefixLength)),
                    $file,
                    \array_merge($config, ['set_last_path' => false])
                );
            }

            $this->last = $path;

            return $this;
        }

        if ($value instanceof PendingFile) {
            $value = $value->localFile();
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

    /**
     * @internal
     */
    public function swap(FilesystemAdapter $adapter): void
    {
        $this->operator->swap($adapter);
    }
}
