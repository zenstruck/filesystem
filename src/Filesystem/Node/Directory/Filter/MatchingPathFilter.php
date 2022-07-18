<?php

namespace Zenstruck\Filesystem\Node\Directory\Filter;

use Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator;
use Symfony\Component\Finder\Iterator\PathFilterIterator;
use Zenstruck\Filesystem\Node;

/**
 * @see PathFilterIterator
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 * @extends MultiplePcreFilterIterator<int,Node>
 *
 * @method Node current()
 */
final class MatchingPathFilter extends MultiplePcreFilterIterator
{
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->path());
    }

    protected function toRegex(string $str): string
    {
        return $this->isRegex($str) ? $str : '/'.\preg_quote($str, '/').'/';
    }
}
