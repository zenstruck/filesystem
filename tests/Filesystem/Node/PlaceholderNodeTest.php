<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\PlaceholderNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class PlaceholderNodeTest extends TestCase
{
    /**
     * @test
     */
    public function exists_is_always_false(): void
    {
        $node = $this->createNode();

        $this->assertFalse($node->exists());
    }

    /**
     * @test
     */
    public function any_other_method_results_in_error(): void
    {
        $node = $this->createNode();

        $this->expectException(\LogicException::class);

        $node->path();
    }

    abstract protected function createNode(): PlaceholderNode;
}
