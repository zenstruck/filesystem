<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements \IteratorAggregate<int,File>
 */
class FileCollection implements \IteratorAggregate, \Countable
{
    /**
     * @param File[] $files
     *
     * @internal
     */
    final public function __construct(private array $files)
    {
    }

    /**
     * @return $this
     */
    final public function add(File $file): static
    {
        $this->files[] = $file;

        return $this;
    }

    /**
     * @return $this
     */
    final public function remove(File $file): static
    {
        foreach ($this->files as $i => $existingFile) {
            if ($file->path() === $existingFile->path()) {
                unset($this->files[$i]);
            }
        }

        return $this;
    }

    final public function has(File $file): bool
    {
        foreach ($this->files as $existingFile) {
            if ($existingFile->path() === $file->path()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return File[]
     */
    public function all(): array
    {
        return $this->files;
    }

    /**
     * @return \Traversable<File>|File[]
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
