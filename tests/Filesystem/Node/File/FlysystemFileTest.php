<?php

namespace Zenstruck\Tests\Filesystem\Node\File;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Tests\Filesystem\Node\FileTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class FlysystemFileTest extends TestCase
{
    use FileTests;

    protected function createFile(\SplFileInfo $file, string $path): File
    {
        return $this->filesystem->write($path, $file)->last()->ensureFile();
    }
}
