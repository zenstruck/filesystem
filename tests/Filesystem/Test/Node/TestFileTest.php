<?php

namespace Zenstruck\Tests\Filesystem\Test\Node;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Test\Node\TestFile;
use Zenstruck\Tests\Filesystem\Node\FileTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class TestFileTest extends TestCase
{
    use FileTests;

    protected function createFile(\SplFileInfo $file, string $path): File
    {
        return new TestFile($this->filesystem->write($path, $file)->last()->ensureFile());
    }
}
