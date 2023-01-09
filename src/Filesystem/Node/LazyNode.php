<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class LazyNode implements Node
{
    use DecoratedNode;

    protected Node $inner;

    protected array $attributes = [];

    /** @var null|Filesystem|callable():Filesystem */
    private $filesystem;

    /** @var null|Path|string|callable():string */
    private $path;

    /**
     * @param null|string|array|callable():string $attributes
     */
    public function __construct(string|callable|array|null $attributes = null)
    {
        if (\is_callable($attributes) || \is_string($attributes)) {
            $this->path = $attributes;
        }

        if (\is_array($attributes)) {
            $this->attributes = $attributes;
        }
    }

    /**
     * @param Filesystem|callable():Filesystem $filesystem
     */
    public function setFilesystem(Filesystem|callable $filesystem): void
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @param string|callable():string $path
     */
    public function setPath(string|callable $path): void
    {
        $this->path = \is_string($path) ? new Path($path) : $path;
    }

    public function path(): Path
    {
        $this->path ??= $this->attributes[__FUNCTION__] ?? null;

        if ($this->path instanceof Path) {
            return $this->path;
        }

        if (\is_callable($this->path)) {
            return $this->path = new Path(($this->path)());
        }

        if (null === $this->path) {
            throw new \RuntimeException('Path not set.');
        }

        return $this->path = new Path($this->path);
    }

    public function dsn(): string
    {
        return $this->attributes[__FUNCTION__] ?? $this->inner()->dsn();
    }

    public function lastModified(): \DateTimeImmutable
    {
        if (!isset($this->attributes[__FUNCTION__])) {
            return $this->inner()->lastModified();
        }

        $lastModified = $this->attributes[__FUNCTION__];

        if ($lastModified instanceof \DateTimeImmutable) {
            return $this->attributes[__FUNCTION__];
        }

        $lastModified = \is_numeric($lastModified) ? \DateTimeImmutable::createFromFormat('U', (string) $lastModified) : new \DateTimeImmutable($lastModified);

        return $lastModified->setTimezone(new \DateTimeZone(\date_default_timezone_get())); // @phpstan-ignore-line
    }

    public function visibility(): string
    {
        return $this->attributes[__FUNCTION__] ?? $this->inner()->visibility();
    }

    public function mimeType(): string
    {
        return $this->attributes[__FUNCTION__] ?? $this->inner()->mimeType();
    }

    public function exists(): bool
    {
        return $this->filesystem()->has($this->path());
    }

    public function refresh(): static
    {
        $this->inner()->refresh();

        $this->attributes = [];

        return $this;
    }

    protected function filesystem(): Filesystem
    {
        if ($this->filesystem instanceof Filesystem) {
            return $this->filesystem;
        }

        if (\is_callable($this->filesystem)) {
            return $this->filesystem = ($this->filesystem)();
        }

        throw new \RuntimeException('Filesystem not set.');
    }
}
