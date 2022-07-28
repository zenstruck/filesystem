<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SlugifyNamer extends BaseNamer
{
    public function generateName(PendingFile $file, object $object, array $config = []): string
    {
        return $this->slugify($file->originalNameWithoutExtension()).self::extensionWithDot($file);
    }
}
