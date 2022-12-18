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

use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixer;
use Psr\Container\ContainerInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Flysystem\AdapterFactory;
use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\FlysystemDirectory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\FlysystemFile;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Stream;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFilesystem implements Filesystem
{
    private Operator $operator;
    private string|\LogicException $last;

    public function __construct(
        FilesystemOperator|FilesystemAdapter|string $flysystem,
        string $name = 'default',
        array|ContainerInterface $features = []
    ) {
        if (\is_string($flysystem)) {
            $flysystem = AdapterFactory::createAdapter($flysystem);
        }

        if ($flysystem instanceof FilesystemAdapter) {
            $flysystem = new Flysystem($flysystem);
        }

        $this->operator = new Operator($flysystem, $name, $features);
        $this->last = new \LogicException('No operations have been performed.');
    }

    public function name(): string
    {
        return $this->operator->name();
    }

    public function node(string $path): File|Directory
    {
        if ($this->operator->fileExists($path)) {
            return new FlysystemFile($path, $this->operator);
        }

        if ($this->operator->directoryExists($path)) {
            return new FlysystemDirectory($path, $this->operator);
        }

        throw new NodeNotFound($path);
    }

    public function file(string $path): File
    {
        return $this->node($path)->ensureFile();
    }

    public function directory(string $path = ''): Directory
    {
        return $this->node($path)->ensureDirectory();
    }

    public function image(string $path): Image
    {
        return $this->node($path)->ensureImage();
    }

    public function has(string $path): bool
    {
        return $this->operator->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        // todo: copy dir?
        $this->operator->copy($source, $destination, $config);
        $this->last = $destination;

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        // todo: move dir?
        $this->operator->move($source, $destination, $config);
        $this->last = $destination;

        return $this;
    }

    public function delete(Directory|string $path, array $config = []): static
    {
        $this->last = new \LogicException('Last operation was a delete so no last node is available.');

        if ($path instanceof Directory) {
            foreach ($path as $node) {
                $this->delete($node->path(), $config);
            }

            return $this;
        }

        if ($this->operator->fileExists($path)) {
            $this->operator->delete($path);

            return $this;
        }

        if ($this->operator->directoryExists($path)) {
            $this->operator->deleteDirectory($path);
        }

        return $this;
    }

    public function mkdir(string $path, array $config = []): static
    {
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
        if ($value instanceof \SplFileInfo && $value->isDir()) {
            $value = (new self(new Flysystem(new LocalFilesystemAdapter($value))))->directory()->recursive();
        }

        if ($value instanceof Directory) {
            $prefixer = new PathPrefixer($path);
            $prefixLength = \mb_strlen($value->path());

            foreach ($value->files() as $file) {
                $this->write($prefixer->prefixPath(\mb_substr($file->path(), $prefixLength)), $file, $config);
            }

            $this->last = $path;

            return $this;
        }

        $closeStream = false;

        if ($value instanceof \SplFileInfo) {
            $value = Stream::open($value, 'r');
            $closeStream = true;
        }

        if ($value instanceof File) {
            $value = $value->read();
            $closeStream = true;
        }

        if (\is_string($value)) {
            $value = Stream::wrap($value);
            $closeStream = true;
        }

        if (\is_resource($value)) {
            $value = Stream::wrap($value);
        }

        if (!$value instanceof Stream) {
            throw new \InvalidArgumentException(\sprintf('Unable to write "%s".', \get_debug_type($value)));
        }

        try {
            $this->operator->writeStream($path, $value->get(), $config);
        } finally {
            $closeStream && $value->close();
        }

        $this->last = $path;

        return $this;
    }

    public function last(): File|Directory
    {
        return \is_string($this->last) ? $this->node($this->last) : throw $this->last;
    }
}
