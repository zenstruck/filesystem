<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends \IteratorAggregate<Node|File|Directory>
 *
 * @method Node[]|File[]|Directory[] getIterator()
 */
interface Directory extends Node, \IteratorAggregate
{
    public function recursive(): static;

    /**
     * Filter nodes (return true = include, return false = exclude).
     *
     * @param callable(Node):bool|callable(File):bool|callable(Directory):bool $predicate
     */
    public function filter(callable $predicate): static;

    /**
     * @return $this<File>|File[]
     */
    public function files(): static;

    /**
     * @return $this<Directory>|Directory[]
     */
    public function directories(): static;

    /**
     * @return $this<File>|File[]
     */
    public function largerThan(int $bytes): static;

    /**
     * @return $this<File>|File[]
     */
    public function smallerThan(int $bytes): static;

    /**
     * @return $this<File>|File[]
     */
    public function olderThan(int|string|\DateTimeInterface $timestamp): static;

    /**
     * @return $this<File>|File[]
     */
    public function newerThan(int|string|\DateTimeInterface $timestamp): static;

    public function matching(string $pattern): static;

    public function notMatching(string $pattern): static;
}
