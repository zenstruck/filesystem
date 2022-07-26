<?php

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Symfony\Component\Finder\Iterator\LazyIterator;
use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem\Adapter\Operator;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\ArchiveFile;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory\Filter\MatchingNameFilter;
use Zenstruck\Filesystem\Node\Directory\Filter\MatchingPathFilter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 * @template T of Node
 * @implements \IteratorAggregate<int,T>
 *
 * @phpstan-import-type GlobalConfig from AdapterFilesystem
 * @phpstan-import-type ZipConfig from ArchiveFile
 */
final class Directory implements Node, \IteratorAggregate
{
    use IsNode {
        __construct as traitConstruct;
    }

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
        $this->traitConstruct($attributes, $operator);
    }

    public function mimeType(): string
    {
        return '(directory)';
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
     * Include only files larger than $size.
     *
     * @param string|int $size Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     *
     * @return self<File>
     */
    public function largerThan(string|int $size): self
    {
        $size = Information::from($size)->bytes();

        // @phpstan-ignore-next-line
        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isLargerThan($size));
    }

    /**
     * Include only files smaller than $size.
     *
     * @param string|int $size Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     *
     * @return self<File>
     */
    public function smallerThan(string|int $size): self
    {
        $size = Information::from($size)->bytes();

        // @phpstan-ignore-next-line
        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isSmallerThan($size));
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
        $min = Information::from($min)->bytes();
        $max = Information::from($max)->bytes();

        // @phpstan-ignore-next-line
        return $this->filter(static fn(Node $node) => $node instanceof File && $node->size()->isWithin($min, $max));
    }

    /**
     * Include only files last modified before $date.
     *
     * @param string             $date Valid {@see strtotime} textual datetime description (ie "yesterday")
     * @param int                $date Specific timestamp
     * @param \DateTimeInterface $date Specific DateTime
     *
     * @return self<Node>
     */
    public function olderThan(\DateTimeInterface|int|string $date): self
    {
        $date = self::parseDateTime($date);

        return $this->filter(static fn(Node $node) => $node->lastModified() < $date);
    }

    /**
     * Include only files last modified after $date.
     *
     * @param string             $date Valid {@see strtotime} textual datetime description (ie "yesterday")
     * @param int                $date Specific timestamp
     * @param \DateTimeInterface $date Specific DateTime
     *
     * @return self<Node>
     */
    public function newerThan(\DateTimeInterface|int|string $date): self
    {
        $date = self::parseDateTime($date);

        return $this->filter(static fn(Node $node) => $node->lastModified() > $date);
    }

    /**
     * Include only files modified after (or on) $min and before (or on) $max.
     *
     * @param string             $min Valid {@see strtotime} textual datetime description (ie "yesterday")
     * @param int                $min Specific timestamp
     * @param \DateTimeInterface $min Specific DateTime
     * @param string             $max Valid {@see strtotime} textual datetime description (ie "yesterday")
     * @param int                $max Specific timestamp
     * @param \DateTimeInterface $max Specific DateTime
     *
     * @return self<Node>
     */
    public function modifiedBetween(\DateTimeInterface|int|string $min, \DateTimeInterface|int|string $max): self
    {
        $min = self::parseDateTime($min);
        $max = self::parseDateTime($max);

        return $this->filter(static function(Node $node) use ($min, $max): bool {
            return $node->lastModified() >= $min && $node->lastModified() <= $max;
        });
    }

    /**
     * Adds rules that file/directory names must match.
     *
     * @example "*.jpg"
     * @example "'/\.jpg/" (same as above)
     * @example "foo.jpg"
     * @example ["*.jpg", "*.png"]
     *
     * @param string|string[] $pattern A pattern (a regexp, a glob, or a string) or an array of patterns
     *
     * @return self<Node>
     */
    public function matchingName(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->names = \array_merge($this->names, (array) $pattern);

        return $clone;
    }

    /**
     * Adds rules that file/directory names must not match.
     *
     * @example "*.jpg"
     * @example "'/\.jpg/" (same as above)
     * @example "foo.jpg"
     * @example ["*.jpg", "*.png"]
     *
     * @param string|string[] $pattern A pattern (a regexp, a glob, or a string) or an array of patterns
     *
     * @return self<Node>
     */
    public function notMatchingName(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->notNames = \array_merge($this->notNames, (array) $pattern);

        return $clone;
    }

    /**
     * Adds rules that path's must match.
     *
     * @example "some/dir"
     * @example ["some/dir", "another/dir"]
     *
     * @param string|string[] $pattern A pattern (a regexp or a string) or an array of patterns
     *
     * @return self<Node>
     */
    public function matchingPath(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->paths = \array_merge($this->paths, (array) $pattern);

        return $clone;
    }

    /**
     * Adds rules that path's must not match.
     *
     * @example "some/dir"
     * @example ["some/dir", "another/dir"]
     *
     * @param string|string[] $pattern A pattern (a regexp or a string) or an array of patterns
     *
     * @return self<Node>
     */
    public function notMatchingPath(string|array $pattern): self
    {
        $clone = clone $this;
        $clone->notPaths = \array_merge($this->notPaths, (array) $pattern);

        return $clone;
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
        $iterator = new \IteratorIterator(
            new LazyIterator(function(): \Iterator {
                $listing = $this->operator()->listContents($this->path(), $this->recursive);

                foreach ($listing as $attributes) {
                    yield match (true) {
                        $attributes instanceof FileAttributes => new File($attributes, $this->operator()),
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

        /** @var \Traversable<T> $iterator */
        yield from $iterator;
    }

    /**
     * @see ArchiveFile::zip()
     *
     * @param GlobalConfig|ZipConfig|array<string,mixed> $config
     */
    public function zip(?string $filename = null, array $config = []): ArchiveFile
    {
        return ArchiveFile::zip($this, $filename, $config);
    }
}
