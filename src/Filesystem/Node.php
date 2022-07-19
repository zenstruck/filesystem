<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Zenstruck\Filesystem\Flysystem\Operator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class Node
{
    private string $path;
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

    public function __toString(): string
    {
        return $this->path;
    }

    final public function path(): string
    {
        return $this->path;
    }

    /**
     * Returns the file or directory name (with extension if applicable).
     *
     * @example If $path is "foo/bar/baz.txt", returns "baz.txt"
     * @example If $path is "foo/bar/baz", returns "baz"
     */
    final public function name(): string
    {
        return \pathinfo($this->path(), \PATHINFO_BASENAME);
    }

    /**
     * Returns the "parent" directory path.
     *
     * @example If $path is "foo/bar/baz", returns "foo/bar"
     */
    final public function dirname(): string
    {
        return \pathinfo($this->path(), \PATHINFO_DIRNAME);
    }

    /**
     * @return \DateTimeImmutable In the PHP default timezone
     */
    final public function lastModified(): \DateTimeImmutable
    {
        return $this->lastModified ??= self::parseDateTime($this->operator->lastModified($this->path()));
    }

    /**
     * @see FilesystemOperator::visibility()
     */
    final public function visibility(): string
    {
        return $this->visibility ??= $this->operator->visibility($this->path());
    }

    /**
     * Check if the node still exists.
     */
    final public function exists(): bool
    {
        return $this->operator->has($this->path());
    }

    /**
     * Clear any cached metadata.
     */
    public function refresh(): static
    {
        unset($this->visibility, $this->lastModified);

        return $this;
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
}
