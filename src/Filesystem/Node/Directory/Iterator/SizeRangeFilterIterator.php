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

use Symfony\Component\Finder\Comparator\NumberComparator;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @extends \FilterIterator<int,Node,\Iterator<Node>>
 * @method Node current()
 */
final class SizeRangeFilterIterator extends \FilterIterator
{
    /**
     * @param \Iterator<Node>    $iterator
     * @param NumberComparator[] $comparators
     */
    public function __construct(\Iterator $iterator, private array $comparators)
    {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        if (!($node = $this->current()) instanceof File) {
            return true;
        }

        $size = $node->size();

        foreach ($this->comparators as $compare) {
            if (!$compare->test($size)) {
                return false;
            }
        }

        return true;
    }
}
