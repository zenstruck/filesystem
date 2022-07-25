<?php

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait WrappedFilesystem
{
    public function name(): string
    {
        return $this->inner()->name();
    }

    public function node(string $path = ''): File|Directory
    {
        return $this->inner()->node($path);
    }

    public function file(string $path): File
    {
        return $this->inner()->file($path);
    }

    public function image(string $path, array $config = []): Image
    {
        return $this->inner()->image($path, $config);
    }

    public function directory(string $path = ''): Directory
    {
        return $this->inner()->directory($path);
    }

    public function exists(string $path = ''): bool
    {
        return $this->inner()->exists($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        $this->inner()->copy($source, $destination, $config);

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        $this->inner()->move($source, $destination, $config);

        return $this;
    }

    public function delete(Directory|string $path = '', array $config = []): static
    {
        $this->inner()->delete($path, $config);

        return $this;
    }

    public function mkdir(string $path = '', array $config = []): static
    {
        $this->inner()->mkdir($path, $config);

        return $this;
    }

    public function chmod(string $path, string $visibility): static
    {
        $this->inner()->chmod($path, $visibility);

        return $this;
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        $this->inner()->write($path, $value, $config);

        return $this;
    }

    public function last(): File|Directory
    {
        return $this->inner()->last();
    }

    abstract protected function inner(): Filesystem;
}
