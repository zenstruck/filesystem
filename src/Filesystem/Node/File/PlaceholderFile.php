<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\PlaceholderNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PlaceholderFile extends PlaceholderNode implements File
{
    use DecoratedFile;

    protected function inner(): File
    {
        throw new \LogicException('This is a placeholder file only.');
    }
}
