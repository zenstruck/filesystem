<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LazyImage extends Image implements LazyNode
{
    use IsLazyFile;
}
