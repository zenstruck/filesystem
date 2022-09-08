<?php

namespace Zenstruck\Filesystem\Node\Directory;

use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\IsLazyNode;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LazyDirectory implements Directory, LazyNode
{
    use IsLazyNode, WrappedDirectory {
        IsLazyNode::path insteadof WrappedDirectory;
    }

    /**
     * @return Directory<Node>
     */
    protected function inner(): Directory
    {
        return $this->inner ??= $this->filesystem()->directory($this->path());
    }
}
