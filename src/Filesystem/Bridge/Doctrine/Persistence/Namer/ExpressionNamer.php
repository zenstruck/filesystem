<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionNamer extends BaseNamer
{
    public function generateName(Node $node, object $object, array $config = []): string
    {
        return \strtr(
            $config['expression'] ?? throw new \LogicException('An "expression" option must be added to your column options.'),
            [
                '{name}' => $this->slugify(self::nameWithoutExtension($node)),
                '{ext}' => self::extensionWithDot($node),
            ]
        );
    }
}
