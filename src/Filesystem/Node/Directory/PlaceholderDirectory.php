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

use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\PlaceholderNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PlaceholderDirectory extends PlaceholderNode implements Directory
{
    public function recursive(): static
    {
        return clone $this;
    }

    public function first(): ?Node
    {
        return null;
    }

    public function filter(callable $predicate): static
    {
        return clone $this;
    }

    public function files(): static
    {
        return clone $this;
    }

    public function directories(): static
    {
        return clone $this;
    }

    public function size(array|int|string $sizes): static
    {
        return clone $this;
    }

    public function largerThan(int|string $size): static
    {
        return clone $this;
    }

    public function smallerThan(int|string $size): static
    {
        return clone $this;
    }

    public function date(array|string $dates): static
    {
        return clone $this;
    }

    public function olderThan(\DateTimeInterface|int|string $timestamp): static
    {
        return clone $this;
    }

    public function newerThan(\DateTimeInterface|int|string $timestamp): static
    {
        return clone $this;
    }

    public function matchingFilename(array|string $patterns): static
    {
        return clone $this;
    }

    public function notMatchingFilename(array|string $patterns): static
    {
        return clone $this;
    }

    public function matchingPath(array|string $patterns): static
    {
        return clone $this;
    }

    public function notMatchingPath(array|string $patterns): static
    {
        return clone $this;
    }

    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }

    protected function inner(): Directory
    {
        throw new \LogicException('This is a placeholder directory only.');
    }
}
