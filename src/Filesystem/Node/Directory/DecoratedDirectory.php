<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\Directory;

use Zenstruck\Filesystem\Node\Directory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait DecoratedDirectory
{
    public function recursive(): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->recursive();

        return $clone;
    }

    public function filter(callable $predicate): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->filter($predicate);

        return $clone;
    }

    public function files(): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->files();

        return $clone;
    }

    public function directories(): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->directories();

        return $clone;
    }

    public function size(array|int|string $sizes): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->size($sizes);

        return $clone;
    }

    public function date(array|string $dates): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->date($dates);

        return $clone;
    }

    public function matchingFilename(array|string $patterns): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->matchingFilename($patterns);

        return $clone;
    }

    public function notMatchingFilename(array|string $patterns): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->notMatchingFilename($patterns);

        return $clone;
    }

    public function matchingPath(array|string $patterns): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->matchingPath($patterns);

        return $clone;
    }

    public function notMatchingPath(array|string $patterns): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->notMatchingPath($patterns);

        return $clone;
    }

    public function largerThan(int|string $size): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->largerThan($size);

        return $clone;
    }

    public function smallerThan(int|string $size): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->smallerThan($size);

        return $clone;
    }

    public function olderThan(\DateTimeInterface|int|string $timestamp): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->olderThan($timestamp);

        return $clone;
    }

    public function newerThan(\DateTimeInterface|int|string $timestamp): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->newerThan($timestamp);

        return $clone;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->inner()->getIterator();
    }

    abstract protected function inner(): Directory;
}
