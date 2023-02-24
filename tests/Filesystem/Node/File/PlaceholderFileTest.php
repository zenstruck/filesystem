<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File\PlaceholderFile;
use Zenstruck\Tests\Filesystem\Node\PlaceholderNodeTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PlaceholderFileTest extends PlaceholderNodeTest
{
    protected function createNode(): PlaceholderFile
    {
        return new PlaceholderFile();
    }
}
