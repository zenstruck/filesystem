<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SlugifyNamer extends BaseNamer
{
    public function generateName(Node $node, object $object, array $config = []): string
    {
        return $this->slugify(self::nameWithoutExtension($node)).self::extensionWithDot($node);
    }
}
