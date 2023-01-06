<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\Directory\Iterator;

use Symfony\Component\Finder\Glob;
use Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 *
 * @extends MultiplePcreFilterIterator<int,Node>
 *
 * @method Node current()
 */
final class FilenameFilterIterator extends MultiplePcreFilterIterator
{
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->path()->name());
    }

    protected function toRegex(string $str): string
    {
        return $this->isRegex($str) ? $str : Glob::toRegex($str);
    }
}
