<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait WrappedNode
{
    public function __toString(): string
    {
        return (string) $this->inner();
    }

    public function path(): string
    {
        return $this->inner()->path();
    }

    public function name(): string
    {
        return $this->inner()->name();
    }

    public function dirname(): string
    {
        return $this->inner()->dirname();
    }

    public function lastModified(): \DateTimeImmutable
    {
        return $this->inner()->lastModified();
    }

    public function visibility(): string
    {
        return $this->inner()->visibility();
    }

    public function exists(): bool
    {
        return $this->inner()->exists();
    }

    public function mimeType(): string
    {
        return $this->inner()->mimeType();
    }

    public function refresh(): static
    {
        $this->inner()->refresh();

        return $this;
    }

    public function isFile(): bool
    {
        return $this->inner()->isFile();
    }

    public function isDirectory(): bool
    {
        return $this->inner()->isDirectory();
    }

    public function isImage(array $config = []): bool
    {
        return $this->inner()->isImage($config);
    }

    public function ensureDirectory(): Directory
    {
        return $this->inner()->ensureDirectory();
    }

    public function ensureFile(): File
    {
        return $this->inner()->ensureFile();
    }

    public function ensureImage(array $config = []): Image
    {
        return $this->inner()->ensureImage($config);
    }

    public function serialize(): string
    {
        throw new \BadMethodCallException(\sprintf('Cannot serialize %s.', static::class));
    }

    public static function unserialize(string $serialized, MultiFilesystem $filesystem): File|Image|Directory
    {
        throw new \BadMethodCallException(\sprintf('Cannot unserialize %s.', static::class));
    }

    abstract protected function inner(): Node;
}
