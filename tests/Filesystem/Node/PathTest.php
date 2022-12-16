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
use Zenstruck\Filesystem\Node\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PathTest extends TestCase
{
    /**
     * @test
     */
    public function can_handle_multi_part_extensions(): void
    {
        $path = new Path('foo/bar.tar.gz');

        $this->assertSame('bar.tar.gz', $path->name());
        $this->assertSame('tar.gz', $path->extension());
        $this->assertSame('bar', $path->basename());
        $this->assertSame('foo', $path->dirname());
    }
}
