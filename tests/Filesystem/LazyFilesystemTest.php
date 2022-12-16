<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\LazyFilesystem;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyFilesystemTest extends FilesystemTest
{
    protected function createFilesystem(): Filesystem
    {
        return new LazyFilesystem(fn() => in_memory_filesystem());
    }
}
