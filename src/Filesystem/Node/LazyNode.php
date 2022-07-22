<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
interface LazyNode
{
    public function setFilesystem(Filesystem $filesystem): void;
}
