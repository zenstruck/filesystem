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

use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\FlysystemFile;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\FlysystemNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemDirectory extends FlysystemNode implements Directory
{
    private bool $recursive = false;
    private array $filters = [];

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
        foreach ($this->operator->listContents($this->path(), $this->recursive) as $attributes) {
            $node = $attributes->isFile() ? new FlysystemFile($attributes->path(), $this->operator) : new self($attributes->path(), $this->operator);

            foreach ($this->filters as $filter) {
                if (!$filter($node)) {
                    continue 2;
                }
            }

            yield $node;
        }
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
        return $this->filter(fn(Node $n) => $n instanceof File);
    }

    public function directories(): static
    {
        return $this->filter(fn(Node $n) => $n instanceof Directory);
    }

    public function largerThan(int $bytes): static
    {
        return $this->files()->filter(fn(File $file) => $file->size() > $bytes);
    }

    public function smallerThan(int $bytes): static
    {
        return $this->files()->filter(fn(File $file) => $file->size() < $bytes);
    }

    public function olderThan(\DateTimeInterface|int|string $timestamp): static
    {
        $timestamp = self::normalizeTimestamp($timestamp);

        return $this->files()->filter(fn(File $file) => $file->lastModified()->getTimestamp() < $timestamp);
    }

    public function newerThan(\DateTimeInterface|int|string $timestamp): static
    {
        $timestamp = self::normalizeTimestamp($timestamp);

        return $this->files()->filter(fn(File $file) => $file->lastModified()->getTimestamp() > $timestamp);
    }

    public function matching(string $pattern): static
    {
        return $this->filter(fn(Node $node) => \fnmatch($pattern, $node->path()->toString()));
    }

    public function notMatching(string $pattern): static
    {
        return $this->filter(fn(Node $node) => !\fnmatch($pattern, $node->path()->toString()));
    }

    public function ensureImage(): Image
    {
        throw new NodeTypeMismatch(\sprintf('Expected node at path "%s" to be an image but is a directory.', $this->path()));
    }

    private static function normalizeTimestamp(\DateTimeInterface|int|string $timestamp): int
    {
        if (\is_numeric($timestamp)) {
            return (int) $timestamp;
        }

        if (!$timestamp instanceof \DateTimeInterface) {
            $timestamp = new \DateTimeImmutable($timestamp);
        }

        return $timestamp->getTimestamp();
    }
}
