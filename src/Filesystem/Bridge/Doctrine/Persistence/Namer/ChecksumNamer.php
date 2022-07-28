<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ChecksumNamer extends BaseNamer
{
    public function generateName(PendingFile $file, object $object, array $config = []): string
    {
        // todo customize algorithm
        return $file->checksum()->toString().self::extensionWithDot($file);
    }
}
