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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Dsn implements \Stringable
{
    /** @var array{0:string|null,1:string} */
    private array $parts;
    private Path $path;

    private function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function wrap(string|self $value): self
    {
        return $value instanceof self ? $value : new self($value);
    }

    public static function create(string $filesystem, Path|string $path): self
    {
        $dsn = new self("{$filesystem}://{$path}");

        if ($path instanceof Path) {
            $dsn->path = $path;
        }

        return $dsn;
    }

    /**
     * @return array{0:string|null,1:string}
     */
    public static function normalize(string $value): array
    {
        if (2 === \count($parts = \explode('://', $value, 2))) {
            return $parts; // @phpstan-ignore-line
        }

        return [null, $value];
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function filesystem(): string
    {
        return $this->parts()[0] ?? throw new \InvalidArgumentException(\sprintf('"%s" is an invalid DSN value.', $this->value));
    }

    public function path(): Path
    {
        return $this->path ??= new Path($this->parts()[1]);
    }

    /**
     * @return array{0:string|null,1:string}
     */
    private function parts(): array
    {
        return $this->parts ??= self::normalize($this->value);
    }
}
