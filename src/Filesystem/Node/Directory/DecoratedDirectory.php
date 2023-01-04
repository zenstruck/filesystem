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

    public function largerThan(int $bytes): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->largerThan($bytes);

        return $clone;
    }

    public function smallerThan(int $bytes): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->smallerThan($bytes);

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

    public function matching(string $pattern): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->matching($pattern);

        return $clone;
    }

    public function notMatching(string $pattern): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->notMatching($pattern);

        return $clone;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->inner()->getIterator();
    }

    abstract protected function inner(): Directory;
}
