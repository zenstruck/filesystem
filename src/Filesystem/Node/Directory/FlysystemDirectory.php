<?php

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
 *
 * @implements Directory<Node>
 */
final class FlysystemDirectory extends FlysystemNode implements Directory
{
    private bool $recursive = false;

    /** @var array<callable(Node):bool> */
    private array $filters = [];

    public function exists(): bool
    {
        return $this->flysystem->directoryExists($this->path());
    }

    public function mimeType(): string
    {
        return 'dir';
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->flysystem->listContents($this->path(), $this->recursive) as $attributes) {
            $node = $attributes->isFile() ? new FlysystemFile($attributes->path(), $this->flysystem) : new self($attributes->path(), $this->flysystem);

            foreach ($this->filters as $filter) {
                if (!$filter($node)) {
                    continue 2;
                }
            }

            yield $node;
        }
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

    public function files(): self // @phpstan-ignore-line
    {
        return $this->filter(fn(Node $n) => $n instanceof File);
    }

    public function directories(): self // @phpstan-ignore-line
    {
        return $this->filter(fn(Node $n) => $n instanceof Directory);
    }

    public function ensureImage(): Image
    {
        throw new NodeTypeMismatch(\sprintf('Expected node at path "%s" to be an image but is a directory.', $this->path()));
    }
}
