<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\ArchiveFile;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 *
 * @template T of Node
 * @extends \IteratorAggregate<int,T>
 *
 * @phpstan-import-type ZipConfig from ArchiveFile
 */
interface Directory extends Node, \IteratorAggregate
{
    /**
     * @return $this
     */
    public function recursive(): self;

    /**
     * Filter {@see Node}'s (return true = include, return false = exclude).
     *
     * @param callable(Node):bool $predicate
     *
     * @return $this
     */
    public function filter(callable $predicate): self;

    /**
     * Include only files larger than $size.
     *
     * @param string|int $size Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     *
     * @return self<File>
     */
    public function largerThan(string|int $size): self;

    /**
     * Include only files smaller than $size.
     *
     * @param string|int $size Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     *
     * @return self<File>
     */
    public function smallerThan(string|int $size): self;

    /**
     * Include only files larger than (or equal to) $min and smaller than (or equal to) $max.
     *
     * @param string|int $min Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     * @param string|int $max Bytes (ie 546548) or a human-readable format (ie "1.1kB", "3.42 MiB").
     *
     * @return self<File>
     */
    public function sizeWithin(string|int $min, string|int $max): self;

    /**
     * Include only nodes last modified before $date.
     *
     * @param string             $date Valid {@see strtotime} textual datetime description (ie "yesterday")
     * @param int                $date Specific timestamp
     * @param \DateTimeInterface $date Specific DateTime
     *
     * @return $this
     */
    public function olderThan(\DateTimeInterface|int|string $date): self;

    /**
     * Include only nodes last modified after $date.
     *
     * @param string             $date Valid {@see strtotime} textual datetime description (ie "yesterday")
     * @param int                $date Specific timestamp
     * @param \DateTimeInterface $date Specific DateTime
     *
     * @return $this
     */
    public function newerThan(\DateTimeInterface|int|string $date): self;

    /**
     * Include only nodes modified after (or on) $min and before (or on) $max.
     *
     * @param string             $min Valid {@see strtotime} textual datetime description (ie "yesterday")
     * @param int                $min Specific timestamp
     * @param \DateTimeInterface $min Specific DateTime
     * @param string             $max Valid {@see strtotime} textual datetime description (ie "yesterday")
     * @param int                $max Specific timestamp
     * @param \DateTimeInterface $max Specific DateTime
     *
     * @return $this
     */
    public function modifiedBetween(\DateTimeInterface|int|string $min, \DateTimeInterface|int|string $max): self;

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
     * @return $this
     */
    public function matchingName(string|array $pattern): self;

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
     * @return $this
     */
    public function notMatchingName(string|array $pattern): self;

    /**
     * Adds rules that path's must match.
     *
     * @example "some/dir"
     * @example ["some/dir", "another/dir"]
     *
     * @param string|string[] $pattern A pattern (a regexp or a string) or an array of patterns
     *
     * @return $this
     */
    public function matchingPath(string|array $pattern): self;

    /**
     * Adds rules that path's must not match.
     *
     * @example "some/dir"
     * @example ["some/dir", "another/dir"]
     *
     * @param string|string[] $pattern A pattern (a regexp or a string) or an array of patterns
     *
     * @return $this
     */
    public function notMatchingPath(string|array $pattern): self;

    /**
     * @return self<File>
     */
    public function files(): self;

    /**
     * @return self<Directory<Node>>
     */
    public function directories(): self;

    /**
     * @see ArchiveFile::zip()
     *
     * @param ZipConfig|array<string,mixed> $config
     */
    public function zip(?string $filename = null, array $config = []): ArchiveFile;

    /**
     * @return \Traversable<T>|T[]
     */
    public function getIterator(): \Traversable;
}
