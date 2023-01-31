<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\Directory;

use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyDirectory extends LazyNode implements Directory
{
    use DecoratedDirectory;

    public function ensureDirectory(): self
    {
        return $this;
    }

    protected function inner(): Directory
    {
        return $this->inner ??= $this->filesystem()->directory($this->path()); // @phpstan-ignore-line
    }
}
