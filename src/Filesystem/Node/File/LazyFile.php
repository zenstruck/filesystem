<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class LazyFile extends LazyNode implements File
{
    use DecoratedFile;

    public function guessExtension(): ?string
    {
        return $this->path()->extension() ?? $this->inner()->guessExtension();
    }

    protected function inner(): File
    {
        return $this->inner ??= $this->filesystem()->file($this->path()); // @phpstan-ignore-line
    }
}
