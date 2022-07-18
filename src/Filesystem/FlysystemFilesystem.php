<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFilesystem implements Filesystem
{
    public function __construct(private FilesystemOperator $flysystem)
    {
    }

    public function node(string $path = ''): Node
    {
        if ($this->flysystem->fileExists($path)) {
            return new File(new FileAttributes($path), $this->flysystem);
        }

        if ($this->flysystem->directoryExists($path)) {
            return new Directory(new DirectoryAttributes($path), $this->flysystem);
        }

        throw new \RuntimeException('not found'); // todo
    }

    public function file(string $path): File
    {
        $node = $this->node($path);

        return $node instanceof File ? $node : throw new \RuntimeException('node type mismatch'); // todo
    }

    public function directory(string $path = ''): Directory
    {
        $node = $this->node($path);

        return $node instanceof Directory ? $node : throw new \RuntimeException('node type mismatch'); // todo
    }

    public function exists(string $path = ''): bool
    {
        return $this->flysystem->has($path);
    }

    public function copy(string $source, string $destination): void
    {
        $this->flysystem->move($source, $destination);
    }

    public function move(string $source, string $destination): void
    {
        $this->flysystem->move($source, $destination);
    }

    public function delete(string $path = ''): void
    {
        if ($this->flysystem->directoryExists($path)) {
            $this->flysystem->deleteDirectory($path);
        }

        $this->flysystem->delete($path);
    }

    public function mkdir(string $path = ''): void
    {
        $this->flysystem->createDirectory($path);
    }

    public function write(string $path, mixed $value): void
    {
        // TODO: Implement write() method.
    }
}
