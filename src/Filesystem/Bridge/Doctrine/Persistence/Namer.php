<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Namer
{
    /**
     * @param array<string,mixed> $config
     */
    public function generateName(PendingFile $file, object $object, array $config = []): string;
}
