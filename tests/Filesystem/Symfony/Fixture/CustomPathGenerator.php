<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Fixture;

use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Path\Generator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CustomPathGenerator implements Generator
{
    public function generatePath(Node $node, array $context = []): string
    {
        $value = "from/custom.{$node->path()->extension()}";

        foreach ($context as $k => $v) {
            $value .= "{$k}:{$v}";
        }

        return $value;
    }
}
