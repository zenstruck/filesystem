<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
interface LazyNode extends Node
{
    public function setFilesystem(Filesystem $filesystem): void;
}
