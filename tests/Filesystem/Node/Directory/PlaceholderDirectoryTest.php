<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\Directory;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\Directory\PlaceholderDirectory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PlaceholderDirectoryTest extends TestCase
{
    /**
     * @test
     */
    public function exists_is_always_false(): void
    {
        $dir = new PlaceholderDirectory();

        $this->assertFalse($dir->exists());
    }

    /**
     * @test
     */
    public function first_is_always_null(): void
    {
        $dir = new PlaceholderDirectory();

        $this->assertNull($dir->first());
    }

    /**
     * @test
     */
    public function iterator_is_always_empty(): void
    {
        $dir = new PlaceholderDirectory();

        $this->assertEmpty(
            $dir
                ->recursive()
                ->files()
                ->directories()
                ->size('foo')
                ->smallerThan('foo')
                ->largerThan('foo')
                ->date('foo')
                ->olderThan('foo')
                ->newerThan('foo')
                ->matchingPath('foo')
                ->notMatchingPath('foo')
                ->matchingFilename('foo')
                ->notMatchingFilename('foo')
                ->getIterator()
        );
    }

    /**
     * @test
     */
    public function cannot_call_node_methods(): void
    {
        $dir = new PlaceholderDirectory();

        $this->expectException(\LogicException::class);

        $dir->path();
    }
}
