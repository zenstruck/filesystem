<?php

namespace Zenstruck\Tests\Filesystem\Test\Node;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Test\Node\TestDirectory;
use Zenstruck\Tests\Filesystem\Node\DirectoryTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestDirectoryTest extends TestCase
{
    use DirectoryTests;

    protected function createDirectory(\SplFileInfo $directory, string $path): Directory
    {
        return new TestDirectory(in_memory_filesystem()->write($path, $directory)->directory($path));
    }
}
