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

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\DecoratedFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class TestFile extends TestNode implements File
{
    use DecoratedFile, FileAssertions;

    public function __construct(private File $inner)
    {
    }

    protected function inner(): File
    {
        return $this->inner;
    }
}
