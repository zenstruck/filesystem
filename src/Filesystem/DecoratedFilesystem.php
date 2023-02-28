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

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait DecoratedFilesystem
{
    public function name(): string
    {
        return $this->inner()->name();
    }

    public function node(string $path): Node
    {
        return $this->inner()->node($path);
    }

    public function file(string $path): File
    {
        return $this->inner()->file($path);
    }

    public function directory(string $path = ''): Directory
    {
        return $this->inner()->directory($path);
    }

    public function image(string $path): Image
    {
        return $this->inner()->image($path);
    }

    public function has(string $path): bool
    {
        return $this->inner()->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): File
    {
        return $this->inner()->copy($source, $destination, $config);
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        return $this->inner()->move($source, $destination, $config);
    }

    public function delete(string $path, array $config = []): self
    {
        $this->inner()->delete($path, $config);

        return $this;
    }

    public function mkdir(string $path, Directory|\SplFileInfo|null $content = null, array $config = []): Directory
    {
        return $this->inner()->mkdir($path, $content, $config);
    }

    public function chmod(string $path, string $visibility): Node
    {
        return $this->inner()->chmod($path, $visibility);
    }

    public function write(string $path, mixed $value, array $config = []): File
    {
        return $this->inner()->write($path, $value, $config);
    }

    abstract protected function inner(): Filesystem;
}
