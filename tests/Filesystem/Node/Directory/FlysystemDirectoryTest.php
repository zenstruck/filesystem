<?php

namespace Zenstruck\Tests\Filesystem\Node\Directory;

use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Tests\Filesystem\Node\DirectoryTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemDirectoryTest extends DirectoryTest
{
    protected function createDirectory(\SplFileInfo $directory, string $path): Directory
    {
        return in_memory_filesystem()->write($path, $directory)->directory($path);
    }
}
