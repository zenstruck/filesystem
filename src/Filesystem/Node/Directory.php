<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of Node
 * @extends \IteratorAggregate<T>
 *
 * @method Node[] getIterator()
 */
interface Directory extends Node, \IteratorAggregate
{
    /**
     * @return $this
     */
    public function recursive(): self;

    /**
     * Filter nodes (return true = include, return false = exclude).
     *
     * @param callable(Node):bool $predicate
     *
     * @return $this
     */
    public function filter(callable $predicate): self;

    /**
     * @return self<File>
     */
    public function files(): self;

    /**
     * @return self<Directory<Node>>
     */
    public function directories(): self;
}
