<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionNamer extends BaseNamer
{
    public function generateName(PendingFile $file, object $object, array $config = []): string
    {
        return \strtr(
            $config['expression'] ?? throw new \LogicException('An "expression" option must be added to your column options.'),
            [
                '{name}' => $this->slugify($file->originalNameWithoutExtension()),
                '{ext}' => self::extensionWithDot($file),
            ]
        );
    }
}
