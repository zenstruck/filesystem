<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Symfony\Component\String\ByteString;
use Symfony\Component\String\Slugger\SluggerInterface;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionNamer extends BaseNamer
{
    public function __construct(?SluggerInterface $slugger = null)
    {
        parent::__construct($slugger);
    }

    public function generateName(PendingFile $file, object $object, array $config = []): string
    {
        return \preg_replace_callback(
            '#{(name|ext|checksum|rand)}#',
            function($matches) use ($file) {
                return match ($matches[0]) {
                    '{name}' => $this->slugify($file->originalNameWithoutExtension()),
                    '{ext}' => self::extensionWithDot($file),
                    '{checksum}' => $file->checksum()->toString(),
                    '{rand}' => self::randomString(),
                    default => throw new \LogicException('Invalid match.'),
                };
            },
            $config['expression'] ?? '{name}-{rand}{ext}'
        );
    }

    private static function randomString(): string
    {
        if (!\class_exists(ByteString::class)) {
            throw new \LogicException('symfony/string is required to use a {rand} expression - composer require symfony/string.');
        }

        return ByteString::fromRandom(6, '123456789abcdefghijkmnopqrstuvwxyz')->toString();
    }
}
