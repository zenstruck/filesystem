<?php

namespace Zenstruck\Filesystem\Node;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of Node
 *
 * @implements \IteratorAggregate<int,T>
 */
final class Directory extends Node implements \IteratorAggregate
{
    private bool $recursive = false;

    /** @var array<callable(StorageAttributes):bool> */
    private array $filters = [];

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
     * @return self<File>
     */
    public function files(): self
    {
        $clone = clone $this;
        $clone->filters[] = static fn(StorageAttributes $attr) => $attr->isFile();

        return $clone;
    }

    /**
     * @return self<Directory<Node>>
     */
    public function directories(): self
    {
        $clone = clone $this;
        $clone->filters[] = static fn(StorageAttributes $attr) => $attr->isDir();

        return $clone;
    }

    /**
     * @return \Traversable<T>
     */
    public function getIterator(): \Traversable
    {
        $listing = $this->flysystem->listContents($this->path(), $this->recursive);

        foreach ($this->filters as $filter) {
            $listing = $listing->filter($filter);
        }

        foreach ($listing as $attr) {
            // TODO use StorageAttributes to "pre-cache" metadata
            yield match (true) { // @phpstan-ignore-line
                $attr instanceof FileAttributes => self::createFile($attr->path(), $this->flysystem),
                $attr instanceof DirectoryAttributes => self::createDirectory($attr->path(), $this->flysystem),
                default => throw new \LogicException('Unexpected storage attributes.'),
            };
        }
    }
}
