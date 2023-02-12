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
use League\Flysystem\PathPrefixer;
use Psr\Container\ContainerInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Flysystem\AdapterFactory;
use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\FlysystemDirectory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\FlysystemFile;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\FlysystemNode;
use Zenstruck\Stream;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFilesystem implements Filesystem
{
    private Operator $operator;

    /** @var \Closure():Node|\LogicException */
    private \Closure|\LogicException $last;

    public function __construct(
        FilesystemOperator|FilesystemAdapter|string $flysystem,
        ?string $name = null,
        array|ContainerInterface $features = []
    ) {
        $name ??= 'filesystem'.\spl_object_id($this);

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

    public function node(string $path): Node
    {
        return (new FlysystemNode($path, $this->operator))->ensureExists();
    }

    public function file(string $path): File
    {
        return (new FlysystemFile($path, $this->operator))->ensureExists();
    }

    public function directory(string $path = ''): Directory
    {
        return (new FlysystemDirectory($path, $this->operator))->ensureExists();
    }

    public function image(string $path): Image
    {
        return $this->file($path)->ensureImage();
    }

    public function has(string $path): bool
    {
        return $this->operator->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        // todo: copy dir?
        $this->operator->copy($source, $destination, $config);
        $this->last = fn() => new FlysystemFile($destination, $this->operator);

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        // todo: move dir?
        $this->operator->move($source, $destination, $config);
        $this->last = fn() => new FlysystemFile($destination, $this->operator);

        return $this;
    }

    public function delete(string $path, array $config = []): static
    {
        $this->last = new \LogicException('Last operation was a delete so no last node is available.');

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
        $this->last = fn() => new FlysystemDirectory($path, $this->operator);

        return $this;
    }

    public function chmod(string $path, string $visibility): static
    {
        $this->operator->setVisibility($path, $visibility);
        $this->last = fn() => new FlysystemNode($path, $this->operator);

        return $this;
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        if ($value instanceof \SplFileInfo && $value->isDir()) {
            $value = (new self($value))->directory()->recursive();
        }

        if ($value instanceof Directory) {
            $prefixer = new PathPrefixer($path);
            $prefixLength = \mb_strlen($value->path());
            $progress = $config['progress'] ?? static fn() => null;

            foreach ($value->files() as $file) {
                $this->write($prefixer->prefixPath(\mb_substr($file->path(), $prefixLength)), $file, $config);
                $progress($this->last()->ensureFile());
            }

            $this->last = fn() => new FlysystemDirectory($path, $this->operator);

            return $this;
        }

        $closeStream = false;

        if ($value instanceof \SplFileInfo && !$value instanceof File) {
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

        $this->last = fn() => new FlysystemFile($path, $this->operator);

        return $this;
    }

    public function last(): Node
    {
        return \is_callable($this->last) ? ($this->last)() : throw $this->last;
    }
}
