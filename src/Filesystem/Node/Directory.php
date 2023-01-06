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
     * Filter by file size.
     *
     *     $directory->size('> 10K');
     *     $directory->size('<= 1Ki');
     *     $directory->size(4);
     *     $directory->size(['> 10K', '< 20K'])
     *
     * @param string|int|string[]|int[] $sizes A size range string or an integer or an array of size ranges
     */
    public function size(string|int|array $sizes): static;

    /**
     * Common shortcut for {@see size}.
     */
    public function largerThan(string|int $size): static;

    /**
     * Common shortcut for {@see size}.
     */
    public function smallerThan(string|int $size): static;

    /**
     * Filter by last modified.
     *
     * The date must be something that strtotime() is able to parse:
     *
     *     $directory->date('since yesterday');
     *     $directory->date('until 2 days ago');
     *     $directory->date('> now - 2 hours');
     *     $directory->date('>= 2005-10-15');
     *     $directory->date(['>= 2005-10-15', '<= 2006-05-27']);
     *
     * @param string|string[] $dates A date range string or an array of date ranges
     */
    public function date(string|array $dates): static;

    /**
     * Common shortcut for {@see date}.
     */
    public function olderThan(string|int|\DateTimeInterface $timestamp): static;

    /**
     * Common shortcut for {@see date}.
     */
    public function newerThan(string|int|\DateTimeInterface $timestamp): static;

    /**
     * Adds rules that filenames must match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     *     $directory->matchingFilename('*.php')
     *     $directory->matchingFilename('/\.php$/') // same as above
     *     $directory->matchingFilename('test.php')
     *     $directory->matchingFilename(['test.py', 'test.php'])
     *
     * @param string|string[] $patterns A pattern (a regexp, a glob, or a string) or an array of patterns
     */
    public function matchingFilename(string|array $patterns): static;

    /**
     * Adds rules that filenames must NOT match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     *     $directory->notMatchingFilename('*.php')
     *     $directory->notMatchingFilename('/\.php$/') // same as above
     *     $directory->notMatchingFilename('test.php')
     *     $directory->notMatchingFilename(['test.py', 'test.php'])
     *
     * @param string|string[] $patterns A pattern (a regexp, a glob, or a string) or an array of patterns
     */
    public function notMatchingFilename(string|array $patterns): static;

    /**
     * Adds rules that paths must match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     *     $directory->matchingPath('some/special/dir')
     *     $directory->matchingPath('/some\/special\/dir/') // same as above
     *     $directory->matchingPath(['some dir', 'another/dir'])
     *
     * Use only / as dirname separator.
     *
     * @param string|string[] $patterns A pattern (a regexp or a string) or an array of patterns
     */
    public function matchingPath(string|array $patterns): static;

    /**
     * Adds rules that paths must NOT match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     *     $directory->notMatchingPath('some/special/dir')
     *     $directory->notMatchingPath('/some\/special\/dir/') // same as above
     *     $directory->notMatchingPath(['some dir', 'another/dir'])
     *
     * Use only / as dirname separator.
     *
     * @param string|string[] $patterns A pattern (a regexp or a string) or an array of patterns
     */
    public function notMatchingPath(string|array $patterns): static;
}
