<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\ByteString;
use Symfony\Component\String\Slugger\SluggerInterface;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionNamer extends BaseNamer
{
    public function __construct(private ManagerRegistry $doctrine, ?SluggerInterface $slugger = null)
    {
        parent::__construct($slugger);
    }

    public function generateName(PendingFile $file, object $object, array $config = []): string
    {
        return \preg_replace_callback(
            '#{(name|ext|checksum|rand|objectId)}#',
            function($matches) use ($file, $object) {
                return match ($matches[0]) {
                    '{name}' => $this->slugify($file->originalNameWithoutExtension()),
                    '{ext}' => self::extensionWithDot($file),
                    '{checksum}' => $file->checksum()->toString(),
                    '{rand}' => self::randomString(),
                    '{objectId}' => $this->objectId($object),
                    default => throw new \LogicException('Invalid match.'),
                };
            },
            $config['expression'] ?? '{name}-{objectId}-{rand}{ext}'
        );
    }

    private function objectId(object $object): string
    {
        $om = $this->doctrine->getManagerForClass($object::class) ?? throw new \LogicException(\sprintf('Could not find object manager for "%s".', $object::class));

        $ids = $om->getClassMetadata($object::class)->getIdentifierValues($object);

        // todo compound id's with another entity as identifier
        return \implode('', \array_map(static fn($id) => (string) $id, $ids));
    }

    private static function randomString(int $length = 6): string
    {
        if (!\class_exists(ByteString::class)) {
            throw new \LogicException('symfony/string is required to use a {rand} expression - composer require symfony/string.');
        }

        return ByteString::fromRandom($length, '123456789abcdefghijkmnopqrstuvwxyz')->toString();
    }
}
