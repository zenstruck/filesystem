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
use Zenstruck\Tests\Filesystem\Node\DirectoryTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemDirectoryTest extends TestCase
{
    use DirectoryTests;

    protected function createDirectory(\SplFileInfo $directory, string $path): Directory
    {
        return in_memory_filesystem()->write($path, $directory)->directory($path);
    }
}
