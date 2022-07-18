<?php

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 * @template T of Node
 * @implements \IteratorAggregate<int,T>
 */
final class Directory extends Node implements \IteratorAggregate
{
    private bool $recursive = false;

    /** @var array<callable(Node):bool> */
    private array $filters = [];

    public function __construct(DirectoryAttributes $attributes, FilesystemOperator $flysystem)
    {
        parent::__construct($attributes, $flysystem);
    }

    /**
     * @return self<Node>
     */
    public function recursive(): self
    {
        $clone = clone $this;
        $clone->recursive = true;

        return $clone;
    }

    /**
     * Filter {@see Node}'s (return true = include, return false = exclude).
     *
     * @param callable(Node):bool $predicate
     *
     * @return self<Node>
     */
    public function filter(callable $predicate): self
    {
        $clone = clone $this;
        $clone->filters[] = $predicate;

        return $clone;
    }

    /**
     * Include only files larger than (or equal to) $size.
     *
     * @param string|int $size Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     *
     * @return self<File>
     */
    public function largerThan(string|int $size): self
    {
        $size = FileSize::from($size)->bytes();

        // @phpstan-ignore-next-line
        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isLargerThanOrEqualTo($size));
    }

    /**
     * Include only files smaller than (or equal to) $size.
     *
     * @param string|int $size Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     *
     * @return self<File>
     */
    public function smallerThan(string|int $size): self
    {
        $size = FileSize::from($size)->bytes();

        // @phpstan-ignore-next-line
        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isSmallerThanOrEqualTo($size));
    }

    /**
     * Include only files larger than (or equal to) $min and smaller than (or equal to) $max.
     *
     * @param string|int $min Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     * @param string|int $max Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     *
     * @return self<File>
     */
    public function sizeWithin(string|int $min, string|int $max): self
    {
        $min = FileSize::from($min)->bytes();
        $max = FileSize::from($max)->bytes();

        // @phpstan-ignore-next-line
        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isWithin($min, $max));
    }

    /**
     * @return self<File>
     */
    public function files(): self
    {
        $clone = clone $this;
        $clone->filters[] = static fn(Node $node) => $node instanceof File;

        return $clone;
    }

    /**
     * @return self<Directory<Node>>
     */
    public function directories(): self
    {
        $clone = clone $this;
        $clone->filters[] = static fn(Node $node) => $node instanceof self;

        return $clone;
    }

    /**
     * @return \Traversable<T>|T[]
     */
    public function getIterator(): \Traversable
    {
        $listing = $this->flysystem->listContents($this->path(), $this->recursive);

        foreach ($listing as $attributes) {
            /** @var T $node */
            $node = match (true) {
                $attributes instanceof FileAttributes => new File($attributes, $this->flysystem),
                $attributes instanceof DirectoryAttributes => new self($attributes, $this->flysystem),
                default => throw new \LogicException('Unexpected StorageAttributes object.'),
            };

            foreach ($this->filters as $filter) {
                if (!$filter($node)) {
                    continue 2;
                }
            }

            yield $node;
        }
    }
}
