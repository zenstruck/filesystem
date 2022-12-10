<?php

namespace Zenstruck\Tests\Filesystem\Node\File\Image;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Tests\Filesystem\Node\File\ImageTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FlysystemImageTest extends ImageTest
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

    protected function createFile(\SplFileInfo $file, string $path): Image
    {
        return $this->filesystem->write($path, $file)->last()->ensureImage();
    }
}
