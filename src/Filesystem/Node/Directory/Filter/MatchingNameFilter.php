<?php

namespace Zenstruck\Filesystem\Node\Directory\Filter;

use Symfony\Component\Finder\Glob;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;
use Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator;
use Zenstruck\Filesystem\Node;

/**
 * @see FilenameFilterIterator
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 * @extends MultiplePcreFilterIterator<int,Node>
 *
 * @method Node current()
 */
final class MatchingNameFilter extends MultiplePcreFilterIterator
{
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->name());
    }

    protected function toRegex(string $str): string
    {
        return $this->isRegex($str) ? $str : Glob::toRegex($str);
    }
}
