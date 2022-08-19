<?php

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\StorageAttributes;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\LazyImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait IsNode
{
    protected string $path;
    private \DateTimeImmutable $lastModified;
    private string $visibility;

    public function __construct(StorageAttributes $attributes, protected Operator $operator)
    {
        $this->path = $attributes->path();

        if ($lastModified = $attributes->lastModified()) {
            $this->lastModified = self::parseDateTime($lastModified);
        }

        if ($visibility = $attributes->visibility()) {
            $this->visibility = $visibility;
        }
    }

    final public function __toString(): string
    {
        return $this->path;
    }

    public function serialize(): string
    {
        return $this->operator()->serialize($this->path);
    }

    final public function path(): string
    {
        return $this->path;
    }

    final public function name(): string
    {
        return \pathinfo($this->path(), \PATHINFO_BASENAME);
    }

    final public function dirname(): string
    {
        return \pathinfo($this->path(), \PATHINFO_DIRNAME);
    }

    final public function lastModified(): \DateTimeImmutable
    {
        return $this->lastModified ??= self::parseDateTime($this->operator()->lastModified($this->path()));
    }

    /**
     * @see FilesystemOperator::visibility()
     */
    final public function visibility(): string
    {
        return $this->visibility ??= $this->operator()->visibility($this->path());
    }

    final public function exists(): bool
    {
        return $this->operator()->has($this->path());
    }

    abstract public function mimeType(): string;

    public function refresh(): static
    {
        unset($this->visibility, $this->lastModified);

        return $this;
    }

    final public function ensureFile(): File
    {
        return $this instanceof File ? $this : throw NodeTypeMismatch::expectedFileAt($this->path());
    }

    final public function ensureDirectory(): Directory
    {
        return $this instanceof Directory ? $this : throw NodeTypeMismatch::expectedDirectoryAt($this->path());
    }

    final public function ensureImage(array $config = []): Image
    {
        if ($this instanceof Image) {
            return $this;
        }

        if (!$this->isImage($config)) {
            throw NodeTypeMismatch::expectedImageAt($this->path(), $this->mimeType());
        }

        $image = isset($this->operator) ? new Image($this->path) : new LazyImage($this->path); // @phpstan-ignore-line

        if (isset($this->operator)) {
            $image->operator = $this->operator; // @phpstan-ignore-line
        }

        if (isset($this->visibility)) {
            $image->visibility = $this->visibility; // @phpstan-ignore-line
        }

        if (isset($this->lastModified)) {
            $image->lastModified = $this->lastModified; // @phpstan-ignore-line
        }

        if (isset($this->size)) {
            $image->size = $this->size; // @phpstan-ignore-line
        }

        if (isset($this->mimeType)) {
            $image->mimeType = $this->mimeType; // @phpstan-ignore-line
        }

        if (isset($this->checksum)) {
            $image->checksum = $this->checksum; // @phpstan-ignore-line
        }

        return $image;
    }

    final public function isFile(): bool
    {
        return $this instanceof File;
    }

    final public function isDirectory(): bool
    {
        return $this instanceof Directory;
    }

    final public function isImage(array $config = []): bool
    {
        if ($this instanceof Image) {
            return true;
        }

        if (!$this instanceof File) {
            return false;
        }

        if (($config['check_mime'] ?? false) || !($ext = $this->extension())) {
            return \str_contains($this->mimeType(), 'image/');
        }

        return \in_array(\mb_strtolower($ext), Image::IMAGE_EXTENSIONS, true); // @phpstan-ignore-line
    }

    final protected static function parseDateTime(\DateTimeInterface|int|string $timestamp): \DateTimeImmutable
    {
        if (\is_numeric($timestamp)) {
            $timestamp = \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
        }

        if (\is_string($timestamp)) {
            $timestamp = new \DateTimeImmutable($timestamp);
        }

        if ($timestamp instanceof \DateTime) {
            $timestamp = \DateTimeImmutable::createFromMutable($timestamp);
        }

        if (!$timestamp instanceof \DateTimeImmutable) {
            throw new \RuntimeException('Unable to parse datetime.');
        }

        // ensure in the PHP default timezone
        return $timestamp->setTimezone(new \DateTimeZone(\date_default_timezone_get()));
    }

    protected function operator(): Operator
    {
        return $this->operator;
    }
}
