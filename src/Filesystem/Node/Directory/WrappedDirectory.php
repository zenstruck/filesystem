<?php

namespace Zenstruck\Filesystem\Node\Directory;

use Zenstruck\Filesystem\ArchiveFile;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\WrappedNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait WrappedDirectory
{
    use WrappedNode;

    /** @var Directory<Node> */
    private Directory $inner;

    public function recursive(): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->recursive();

        return $clone;
    }

    public function filter(callable $predicate): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->filter($predicate);

        return $clone;
    }

    public function largerThan(string|int $size): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->largerThan($size);

        return $clone;
    }

    public function smallerThan(string|int $size): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->smallerThan($size);

        return $clone;
    }

    public function sizeWithin(string|int $min, string|int $max): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->sizeWithin($min, $max);

        return $clone;
    }

    public function olderThan(\DateTimeInterface|int|string $date): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->olderThan($date);

        return $clone;
    }

    public function newerThan(\DateTimeInterface|int|string $date): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->newerThan($date);

        return $clone;
    }

    public function modifiedBetween(\DateTimeInterface|int|string $min, \DateTimeInterface|int|string $max): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->modifiedBetween($min, $max);

        return $clone;
    }

    public function matchingName(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->matchingName($pattern);

        return $clone;
    }

    public function notMatchingName(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->notMatchingName($pattern);

        return $clone;
    }

    public function matchingPath(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->matchingPath($pattern);

        return $clone;
    }

    public function notMatchingPath(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->notMatchingPath($pattern);

        return $clone;
    }

    public function files(): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->files();

        return $clone;
    }

    public function directories(): self
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->directories();

        return $clone;
    }

    public function zip(?string $filename = null, array $config = []): ArchiveFile
    {
        return $this->inner()->zip($filename, $config);
    }

    public function getIterator(): \Traversable
    {
        return $this->inner()->getIterator();
    }

    /**
     * @return Directory<Node>
     */
    protected function inner(): Directory
    {
        return $this->inner;
    }
}
