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
 *
 * @internal
 */
abstract class LazyNode implements Node
{
    use DecoratedNode;

    private const PLACEHOLDER = '2519856631465865896663102660600396863735707774042205734238233842';

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
        match (true) {
            \is_string($attributes) && \str_contains($attributes, '://') => $this->attributes = [Metadata::DSN => $attributes],
            \is_string($attributes) || \is_callable($attributes) => $this->path = $attributes,
            \is_array($attributes) => $this->attributes = $attributes,
            default => null,
        };
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
        $this->path ??= $this->attributes[Metadata::PATH] ?? null;

        if ($this->path instanceof Path) {
            return $this->path;
        }

        return $this->path = match (true) {
            \is_callable($this->path) => new Path($this->resolvePath($this->path)),
            \is_string($this->path) => new Path($this->path),
            isset($this->attributes[Metadata::DSN]) => $this->dsn()->path(),
            default => throw new \RuntimeException('Path not set.'),
        };
    }

    public function dsn(): Dsn
    {
        if (!isset($this->attributes[Metadata::DSN])) {
            return $this->inner()->dsn();
        }

        if (!$this->attributes[Metadata::DSN] instanceof Dsn) {
            $this->attributes[Metadata::DSN] = Dsn::wrap($this->attributes[Metadata::DSN]);
        }

        return $this->attributes[Metadata::DSN];
    }

    public function lastModified(): \DateTimeImmutable
    {
        if (!isset($this->attributes[Metadata::LAST_MODIFIED])) {
            return $this->inner()->lastModified();
        }

        $lastModified = $this->attributes[Metadata::LAST_MODIFIED];

        if ($lastModified instanceof \DateTimeImmutable) {
            return $this->attributes[Metadata::LAST_MODIFIED];
        }

        $lastModified = \is_numeric($lastModified) ? \DateTimeImmutable::createFromFormat('U', (string) $lastModified) : new \DateTimeImmutable($lastModified);

        return $lastModified->setTimezone(new \DateTimeZone(\date_default_timezone_get())); // @phpstan-ignore-line
    }

    public function visibility(): string
    {
        return $this->attributes[Metadata::VISIBILITY] ?? $this->inner()->visibility();
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

    private function resolvePath(callable $generator): string
    {
        if (isset($this->attributes[Metadata::FILENAME])) {
            $this->path = new Path($this->attributes[Metadata::FILENAME]);

            return $generator();
        }

        $this->path = new Path(\sprintf('%s%s%s', self::PLACEHOLDER, isset($this->attributes[Metadata::EXTENSION]) ? '.' : '', $this->attributes[Metadata::EXTENSION] ?? ''));
        $path = $generator();

        if (\str_contains($path, self::PLACEHOLDER)) {
            throw new \LogicException('When lazy generating the path, your "path generator" may not use any parts of the path except the extension.');
        }

        return $path;
    }
}
