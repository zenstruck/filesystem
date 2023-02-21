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

    public function copy(string $source, string $destination, array $config = []): File
    {
        // todo: copy dir?
        $this->operator->copy($source, $destination, $config);

        return new FlysystemFile($destination, $this->operator);
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        // todo: move dir?
        $this->operator->move($source, $destination, $config);

        return new FlysystemFile($destination, $this->operator);
    }

    public function delete(string $path, array $config = []): static
    {
        if ($this->operator->fileExists($path)) {
            $this->operator->delete($path);

            return $this;
        }

        if ($this->operator->directoryExists($path)) {
            $this->operator->deleteDirectory($path);
        }

        return $this;
    }

    public function mkdir(string $path, Directory|\SplFileInfo|null $content = null, array $config = []): Directory
    {
        if (!$content) {
            $this->operator->createDirectory($path, $config);

            return new FlysystemDirectory($path, $this->operator);
        }

        if ($content instanceof \SplFileInfo && $content->isDir()) {
            $content = (new self($content))->directory()->recursive();
        }

        if ($content instanceof Directory) {
            $prefixer = new PathPrefixer($path);
            $prefixLength = \mb_strlen($content->path());
            $progress = $config['progress'] ?? static fn() => null;

            foreach ($content->files() as $file) {
                $file = $this->write($prefixer->prefixPath(\mb_substr($file->path(), $prefixLength)), $file, $config); // @phpstan-ignore-line
                $progress($file);
            }

            return new FlysystemDirectory($path, $this->operator);
        }

        throw new \InvalidArgumentException(\sprintf('"%s" is either not a directory or does not exist.', $content));
    }

    public function chmod(string $path, string $visibility): Node
    {
        $this->operator->setVisibility($path, $visibility);

        return new FlysystemNode($path, $this->operator);
    }

    public function write(string $path, mixed $value, array $config = []): File
    {
        if ($value instanceof \SplFileInfo && !$value->isFile()) {
            throw new \InvalidArgumentException(\sprintf('"%s" is either not a file or does not exist.', $value));
        }

        if ($value instanceof \SplFileInfo && !$value instanceof File) {
            $value = Stream::open($value, 'r')->autoClose();
        }

        if ($value instanceof File) {
            $value = $value->stream()->autoClose();
        }

        if (\is_string($value)) {
            $value = Stream::wrap($value)->autoClose();
        }

        if (\is_resource($value)) {
            $value = Stream::wrap($value);
        }

        if (!$value instanceof Stream) {
            throw new \InvalidArgumentException(\sprintf('Unable to write "%s".', \get_debug_type($value)));
        }

        $this->operator->writeStream($path, $value->get(), $config);

        return new FlysystemFile($path, $this->operator);
    }
}
