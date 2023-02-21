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

use League\Flysystem\PathPrefixer;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScopedFilesystem implements Filesystem
{
    private PathPrefixer $prefixer;

    public function __construct(private Filesystem $inner, private string $prefix, private ?string $name = null)
    {
        $this->prefixer = new PathPrefixer($this->prefix);
    }

    public function name(): string
    {
        return $this->name ??= \sprintf('%s-scoped-to-%s', $this->inner->name(), $this->prefix);
    }

    public function node(string $path): Node
    {
        return $this->inner->node($this->prefix($path));
    }

    public function file(string $path): File
    {
        return $this->inner->file($this->prefix($path));
    }

    public function directory(string $path = ''): Directory
    {
        return $this->inner->directory($this->prefix($path));
    }

    public function image(string $path): Image
    {
        return $this->inner->image($this->prefix($path));
    }

    public function has(string $path): bool
    {
        return $this->inner->has($this->prefix($path));
    }

    public function copy(string $source, string $destination, array $config = []): File
    {
        return $this->inner->copy($this->prefix($source), $this->prefix($destination), $config);
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        return $this->inner->move($this->prefix($source), $this->prefix($destination), $config);
    }

    public function delete(string $path, array $config = []): static
    {
        $this->inner->delete($this->prefix($path), $config);

        return $this;
    }

    public function mkdir(string $path, array $config = []): Directory
    {
        return $this->inner->mkdir($this->prefix($path), $config);
    }

    public function chmod(string $path, string $visibility): Node
    {
        return $this->inner->chmod($this->prefix($path), $visibility);
    }

    public function write(string $path, mixed $value, array $config = []): Node
    {
        return $this->inner->write($this->prefix($path), $value, $config);
    }

    public function last(): Node
    {
        $last = $this->inner->last();

        if (\str_starts_with($last->path(), $this->prefix)) {
            return $last;
        }

        throw new \LogicException('Last operation was not on the scoped filesystem.');
    }

    private function prefix(string $path): string
    {
        $path = \trim($path, '\\/');

        if (\str_starts_with($path, $this->prefix)) {
            $path = $this->prefixer->stripPrefix($path);
        }

        return $this->prefixer->prefixPath($path);
    }
}
