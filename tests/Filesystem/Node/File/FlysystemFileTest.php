<?php

namespace Zenstruck\Tests\Filesystem\Node\File;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Tests\Filesystem\Node\FileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemFileTest extends FileTest
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = in_memory_filesystem();
    }

    protected function modifyFile(File $file, \SplFileInfo $new): void
    {
        $this->filesystem->write($file->path(), $new);
    }

    protected function createFile(\SplFileInfo $file, string $path): File
    {
        return $this->filesystem->write($path, $file)->last()->ensureFile();
    }
}
