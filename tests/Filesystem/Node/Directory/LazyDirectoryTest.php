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
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\LazyDirectory;
use Zenstruck\Tests\Filesystem\Node\DirectoryTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyDirectoryTest extends TestCase
{
    use DirectoryTests;

    protected function createDirectory(\SplFileInfo $directory, string $path): Directory
    {
        $fs = in_memory_filesystem();
        $fs->write($path, $directory);

        $ret = new LazyDirectory($path);
        $ret->setFilesystem($fs);

        return $ret;
    }
}
