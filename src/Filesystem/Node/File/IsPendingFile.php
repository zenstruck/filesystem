<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Flysystem\Operator;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait IsPendingFile
{
    public function __construct(private \SplFileInfo $file)
    {
    }

    public function localFile(): \SplFileInfo
    {
        return $this->file;
    }

    protected function operator(): Operator
    {
        return Node::$localOperators[$dir = \dirname($this->file)] ??= (new FlysystemFilesystem($dir))
            ->file($this->file->getFilename())
            ->operator()
        ;
    }
}
