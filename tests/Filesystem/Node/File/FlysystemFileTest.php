<?php

namespace Zenstruck\Tests\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Tests\Filesystem\Node\FileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFileTest extends FileTest
{
    protected function createFile(\SplFileInfo $file, string $path): File
    {
        return $this->filesystem->write($path, $file)->last()->ensureFile();
    }
}
