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
    /** @var array{0:string,1:string} */
    private array $parts;

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

    public static function create(string $filesystem, Path $path): self
    {
        return new self("{$filesystem}://{$path}");
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function filesystem(): string
    {
        return $this->parts()[0];
    }

    public function path(): Path
    {
        return new Path($this->parts()[1]);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function parts(): array
    {
        if (isset($this->parts)) {
            return $this->parts;
        }

        if (2 !== \count($this->parts = \explode('://', $this->value, 2))) { // @phpstan-ignore-line
            throw new \InvalidArgumentException(\sprintf('"%s" is an invalid DSN value.', $this->value));
        }

        return $this->parts; // @phpstan-ignore-line
    }
}
