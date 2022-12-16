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
final class Path implements \Stringable
{
    private const MULTI_EXTENSIONS = ['gz' => 'tar.gz', 'bz2' => 'tar.bz2'];

    /** @var array{0:string,1:string|null} */
    private array $nameParts;

    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Returns the file or directory name (with extension if applicable).
     *
     * @example If path is "foo/bar/baz.txt", returns "baz.txt"
     * @example If path is "foo/bar/baz", returns "baz"
     */
    public function name(): string
    {
        return \pathinfo($this->value, \PATHINFO_BASENAME);
    }

    public function extension(): ?string
    {
        return $this->nameParts()[1];
    }

    /**
     * Returns the file or directory name (without extension).
     *
     * @example If path is "foo/bar/baz.txt", returns "baz"
     * @example If path is "foo/bar/baz", returns "baz"
     */
    public function basename(): string
    {
        return $this->nameParts()[0];
    }

    public function dirname(): string
    {
        return \dirname($this->value);
    }

    /**
     * @return array{0:string,1:string|null}
     */
    private function nameParts(): array
    {
        if (isset($this->nameParts)) {
            return $this->nameParts;
        }

        $name = $this->name();

        if (!$ext = \mb_strtolower(\pathinfo($name, \PATHINFO_EXTENSION)) ?: null) {
            return $this->nameParts = [$name, null];
        }

        if (isset(self::MULTI_EXTENSIONS[$ext]) && \str_ends_with($name, self::MULTI_EXTENSIONS[$ext])) {
            $ext = self::MULTI_EXTENSIONS[$ext];
        }

        return [\mb_substr($name, 0, -(\mb_strlen($ext) + 1)), $ext];
    }
}
