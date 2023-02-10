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

use Symfony\Component\Finder\Comparator\DateComparator;
use Symfony\Component\Finder\Comparator\NumberComparator;
use Symfony\Component\Finder\Finder;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\Iterator\DateRangeFilterIterator;
use Zenstruck\Filesystem\Node\Directory\Iterator\FilenameFilterIterator;
use Zenstruck\Filesystem\Node\Directory\Iterator\LazyIterator;
use Zenstruck\Filesystem\Node\Directory\Iterator\PathFilterIterator;
use Zenstruck\Filesystem\Node\Directory\Iterator\SizeRangeFilterIterator;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\FlysystemFile;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\FlysystemNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FlysystemDirectory extends FlysystemNode implements Directory
{
    private const FILES = 1;
    private const DIRS = 2;

    private ?int $mode = null;
    private bool $recursive = false;
    private array $filters = [];
    private array $filenames = [];
    private array $notFilenames = [];
    private array $paths = [];
    private array $notPaths = [];

    /** @var NumberComparator[] */
    private array $sizes = [];

    /** @var DateComparator[] */
    private array $dates = [];

    public function first(): ?Node
    {
        foreach ($this as $node) {
            return $node;
        }

        return null;
    }

    public function exists(): bool
    {
        return $this->operator->directoryExists($this->path());
    }

    public function mimeType(): string
    {
        return 'dir';
    }

    public function getIterator(): \Traversable
    {
        /** @var \Iterator<Node> $iterator */
        $iterator = new \IteratorIterator($this->nodeIterator());

        if ($this->paths || $this->notPaths) {
            $iterator = new PathFilterIterator($iterator, $this->paths, $this->notPaths);
        }

        if ($this->filenames || $this->notFilenames) {
            $iterator = new FilenameFilterIterator($iterator, $this->filenames, $this->notFilenames);
        }

        if ($this->sizes) {
            $iterator = new SizeRangeFilterIterator($iterator, $this->sizes);
        }

        if ($this->dates) {
            $iterator = new DateRangeFilterIterator($iterator, $this->dates);
        }

        foreach ($this->filters as $filter) {
            $iterator = new \CallbackFilterIterator($iterator, $filter);
        }

        yield from $iterator;
    }

    public function recursive(): static
    {
        $clone = clone $this;
        $clone->recursive = true;

        return $clone;
    }

    public function filter(callable $predicate): static
    {
        $clone = clone $this;
        $clone->filters[] = $predicate;

        return $clone;
    }

    public function files(): static
    {
        $clone = clone $this;
        $clone->mode = self::FILES;

        return $clone;
    }

    public function directories(): static
    {
        $clone = clone $this;
        $clone->mode = self::DIRS;

        return $clone;
    }

    public function size(array|int|string $sizes): static
    {
        self::requiresFinder(__FUNCTION__);

        $clone = clone $this;

        foreach ((array) $sizes as $size) {
            $clone->sizes[] = new NumberComparator($size); // @phpstan-ignore-line
        }

        return $clone;
    }

    public function largerThan(int|string $size): static
    {
        return $this->size('> '.$size);
    }

    public function smallerThan(int|string $size): static
    {
        return $this->size('< '.$size);
    }

    public function date(array|string $dates): static
    {
        self::requiresFinder(__FUNCTION__);

        $clone = clone $this;

        foreach ((array) $dates as $date) {
            $clone->dates[] = new DateComparator($date);
        }

        return $clone;
    }

    public function olderThan(\DateTimeInterface|int|string $timestamp): static
    {
        return $this->date('< '.self::normalizeTimestamp($timestamp));
    }

    public function newerThan(\DateTimeInterface|int|string $timestamp): static
    {
        return $this->date('> '.self::normalizeTimestamp($timestamp));
    }

    public function matchingFilename(array|string $patterns): static
    {
        self::requiresFinder(__FUNCTION__);

        $clone = clone $this;
        $clone->filenames = \array_merge($this->filenames, (array) $patterns);

        return $clone;
    }

    public function notMatchingFilename(array|string $patterns): static
    {
        self::requiresFinder(__FUNCTION__);

        $clone = clone $this;
        $clone->notFilenames = \array_merge($this->notFilenames, (array) $patterns);

        return $clone;
    }

    public function matchingPath(array|string $patterns): static
    {
        self::requiresFinder(__FUNCTION__);

        $clone = clone $this;
        $clone->paths = \array_merge($this->paths, (array) $patterns);

        return $clone;
    }

    public function notMatchingPath(array|string $patterns): static
    {
        self::requiresFinder(__FUNCTION__);

        $clone = clone $this;
        $clone->notPaths = \array_merge($this->notPaths, (array) $patterns);

        return $clone;
    }

    public function ensureImage(): Image
    {
        throw new NodeTypeMismatch(\sprintf('Expected node at path "%s" to be an image but is a directory.', $this->path()));
    }

    private static function normalizeTimestamp(\DateTimeInterface|int|string $timestamp): string
    {
        if (\is_numeric($timestamp)) {
            $timestamp = \DateTimeImmutable::createFromFormat('U', (string) $timestamp) ?: throw new \RuntimeException('Unable to parse timestamp');
        }

        if ($timestamp instanceof \DateTimeInterface) {
            $timestamp = $timestamp->format('c');
        }

        return $timestamp;
    }

    /**
     * @return \Traversable<Node|File|Directory>
     */
    private function nodeIterator(): \Traversable
    {
        return new LazyIterator(function(): \Traversable {
            foreach ($this->operator->listContents($this->path(), $this->recursive) as $attributes) {
                if (self::FILES === $this->mode && !$attributes->isFile()) {
                    continue;
                }

                if (self::DIRS === $this->mode && $attributes->isFile()) {
                    continue;
                }

                yield $attributes->isFile() ? new FlysystemFile($attributes->path(), $this->operator) : new self($attributes->path(), $this->operator);
            }
        });
    }

    private static function requiresFinder(string $method): void
    {
        if (!\class_exists(Finder::class)) {
            throw new \LogicException(\sprintf('Using %s::%s() requires symfony/finder. Install with "composer require symfony/finder".', Directory::class, $method));
        }
    }
}
