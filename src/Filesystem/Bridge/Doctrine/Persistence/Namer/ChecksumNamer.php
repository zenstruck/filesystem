<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ChecksumNamer extends BaseNamer
{
    public function generateName(Node $node, object $object, array $config = []): string
    {
        if (!$node instanceof File) {
            throw new \InvalidArgumentException('Cannot generate checksum for directory.');
        }

        // todo customize algorithm
        return $node->checksum()->toString().self::extensionWithDot($node);
    }
}
