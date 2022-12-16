<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements Directory<Node>
 */
final class TestDirectory extends TestNode implements Directory
{
    /**
     * @param Directory<Node> $inner
     */
    public function __construct(private Directory $inner)
    {
    }

    public function assertCount(int $expected): self
    {
        Assert::that($this)->hasCount($expected, 'Expected Directory to contain {expected} nodes but contains {actual}.');

        return $this;
    }

    public function dump(): self
    {
        $files = \array_map(static fn($d) => (string) $d->path(), \iterator_to_array($this));

        \function_exists('dump') ? dump($files) : \var_dump($files);

        return $this;
    }

    /**
     * @return no-return
     */
    public function dd(): void
    {
        $this->dump();
        exit(1);
    }

    public function recursive(): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->recursive();

        return $clone;
    }

    public function filter(callable $predicate): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->filter($predicate);

        return $clone;
    }

    public function files(): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->files();

        return $clone;
    }

    public function directories(): static
    {
        $clone = clone $this;
        $clone->inner = $this->inner()->directories();

        return $clone;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->inner()->getIterator();
    }

    /**
     * @return Directory<Node>
     */
    protected function inner(): Directory
    {
        return $this->inner;
    }
}
