<?php

namespace Zenstruck\Filesystem\Node\Directory;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Symfony\Component\Finder\Iterator\LazyIterator;
use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\ArchiveFile;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\AdapterNode;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\Filter\MatchingNameFilter;
use Zenstruck\Filesystem\Node\Directory\Filter\MatchingPathFilter;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\AdapterFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @implements Directory<Node>
 */
final class AdapterDirectory extends AdapterNode implements Directory
{
    private bool $recursive = false;

    /** @var array<callable(Node):bool> */
    private array $filters = [];

    /** @var string[] */
    private array $names = [];

    /** @var string[] */
    private array $notNames = [];

    /** @var string[] */
    private array $paths = [];

    /** @var string[] */
    private array $notPaths = [];

    /**
     * @internal
     */
    public function __construct(DirectoryAttributes $attributes, Operator $operator)
    {
        parent::__construct($attributes, $operator);
    }

    public function mimeType(): string
    {
        return '(directory)';
    }

    public function recursive(): self
    {
        $clone = clone $this;
        $clone->recursive = true;

        return $clone;
    }

    public function filter(callable $predicate): self
    {
        $clone = clone $this;
        $clone->filters[] = $predicate;

        return $clone;
    }

    public function largerThan(string|int $size): self // @phpstan-ignore-line
    {
        $size = Information::from($size)->bytes();

        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isLargerThan($size));
    }

    public function smallerThan(string|int $size): self // @phpstan-ignore-line
    {
        $size = Information::from($size)->bytes();

        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isSmallerThan($size));
    }

    public function sizeWithin(string|int $min, string|int $max): self // @phpstan-ignore-line
    {
        $min = Information::from($min)->bytes();
        $max = Information::from($max)->bytes();

        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isWithin($min, $max));
    }

    public function olderThan(\DateTimeInterface|int|string $date): self
    {
        $date = self::parseDateTime($date);

        return $this->filter(static fn(Node $node) => $node->lastModified() < $date);
    }

    public function newerThan(\DateTimeInterface|int|string $date): self
    {
        $date = self::parseDateTime($date);

        return $this->filter(static fn(Node $node) => $node->lastModified() > $date);
    }

    public function modifiedBetween(\DateTimeInterface|int|string $min, \DateTimeInterface|int|string $max): self
    {
        $min = self::parseDateTime($min);
        $max = self::parseDateTime($max);

        return $this->filter(static function(Node $node) use ($min, $max): bool {
            return $node->lastModified() >= $min && $node->lastModified() <= $max;
        });
    }

    public function matchingName(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->names = \array_merge($this->names, (array) $pattern);

        return $clone;
    }

    public function notMatchingName(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->notNames = \array_merge($this->notNames, (array) $pattern);

        return $clone;
    }

    public function matchingPath(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->paths = \array_merge($this->paths, (array) $pattern);

        return $clone;
    }

    public function notMatchingPath(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->notPaths = \array_merge($this->notPaths, (array) $pattern);

        return $clone;
    }

    public function files(): self // @phpstan-ignore-line
    {
        $clone = clone $this;
        $clone->filters[] = static fn(Node $node) => $node instanceof File;

        return $clone;
    }

    public function directories(): self // @phpstan-ignore-line
    {
        $clone = clone $this;
        $clone->filters[] = static fn(Node $node) => $node instanceof self;

        return $clone;
    }

    public function getIterator(): \Traversable
    {
        $iterator = new \IteratorIterator(
            new LazyIterator(function(): \Iterator {
                $listing = $this->operator()->listContents($this->path(), $this->recursive);

                foreach ($listing as $attributes) {
                    yield match (true) {
                        $attributes instanceof FileAttributes => new AdapterFile($attributes, $this->operator()),
                        $attributes instanceof DirectoryAttributes => new self($attributes, $this->operator()),
                        default => throw new \LogicException('Unexpected StorageAttributes object.'),
                    };
                }
            })
        );

        foreach ($this->filters as $filter) {
            $iterator = new \CallbackFilterIterator($iterator, $filter);
        }

        if ($this->names || $this->notNames) {
            $iterator = new MatchingNameFilter($iterator, $this->names, $this->notNames);
        }

        if ($this->paths || $this->notPaths) {
            $iterator = new MatchingPathFilter($iterator, $this->paths, $this->notPaths);
        }

        yield from $iterator;
    }

    public function zip(?string $filename = null, array $config = []): ArchiveFile
    {
        return ArchiveFile::zip($this, $filename, $config);
    }
}
