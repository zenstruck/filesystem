<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\IsLazyNode;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LazyFile implements File, LazyNode
{
    use IsLazyNode, WrappedFile {
        IsLazyNode::path insteadof WrappedFile;
    }

    private File $inner;

    protected function inner(): File
    {
        return $this->inner ??= $this->filesystem()->file($this->path());
    }
}
