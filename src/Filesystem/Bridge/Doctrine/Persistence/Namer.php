<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Namer
{
    /**
     * @param array<string,mixed> $config
     */
    public function generateName(Node $node, object $object, array $config = []): string;
}
