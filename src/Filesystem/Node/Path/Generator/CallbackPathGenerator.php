<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\Path\Generator;

use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Path\Generator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackPathGenerator implements Generator
{
    /** @var callable(Node,array):string */
    private $callback;

    /**
     * @param callable(Node,array):string $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function generatePath(Node $node, array $context = []): string
    {
        return ($this->callback)($node, $context);
    }
}
