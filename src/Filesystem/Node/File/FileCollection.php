<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of File
 * @implements \IteratorAggregate<int,T>
 */
class FileCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param list<T> $files
     *
     * @internal
     */
    final public function __construct(private array $files)
    {
    }

    /**
     * @param T $file
     *
     * @return self<T>
     */
    final public function add(File $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * @param T $file
     *
     * @return self<T>
     */
    final public function remove(File $file): self
    {
        foreach ($this->files as $i => $existingFile) {
            if ($file->path() === $existingFile->path()) {
                unset($this->files[$i]);
            }
        }

        return $this;
    }

    /**
     * @return T[]
     */
    public function all(): array
    {
        return $this->files;
    }

    /**
     * @return \Traversable<T>|T[]
     */
    final public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->all());
    }

    final public function count(): int
    {
        return \count($this->files);
    }
}
