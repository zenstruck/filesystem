<?php

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
