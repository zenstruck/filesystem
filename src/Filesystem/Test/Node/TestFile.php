<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\WrappedFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestFile implements File
{
    use IsTestFile, WrappedFile {
        IsTestFile::ensureFile insteadof WrappedFile;
        IsTestFile::ensureImage insteadof WrappedFile;
        IsTestFile::ensureDirectory insteadof WrappedFile;
        IsTestFile::directory insteadof WrappedFile;
    }

    public function __construct(private File $file)
    {
    }

    protected function inner(): File
    {
        return $this->file;
    }
}
