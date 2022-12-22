<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Filesystem\Doctrine\Attribute\HasFiles;
use Zenstruck\Filesystem\Doctrine\Attribute\Mapping;
use Zenstruck\Filesystem\Doctrine\Types\FileType;
use Zenstruck\Filesystem\Doctrine\Types\ImageType;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\LazyFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class NodeMappingListener
{
    public const OPTION_KEY = '_zsfs';
    public const TYPES = [FileType::class, ImageType::class];
    private const TYPE_NAMES = [FileType::NAME, ImageType::NAME];

    /**
     * @param LoadClassMetadataEventArgs<ClassMetadata<object>,ObjectManager> $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $metadata = $event->getClassMetadata();
        $class = $metadata->getReflectionClass();

        if (!$metadata instanceof ORMClassMetadata) {
            throw new \LogicException('Only ORM is supported currently.');
        }

        if (!$collection = ($class->getAttributes(HasFiles::class)[0] ?? null)?->newInstance()) {
            $collection = new HasFiles();
        }

        foreach ($metadata->fieldMappings as $field => $config) {
            if (!\in_array($config['type'], self::TYPE_NAMES, true)) {
                continue;
            }

            $class = isset($config['declared']) ? new \ReflectionClass($config['declared']) : $metadata->getReflectionClass();
            $property = $class->getProperty($field);

            if (!$mapping = ($property->getAttributes(Mapping::class)[0] ?? null)?->newInstance()) {
                try {
                    $mapping = Mapping::fromArray($config['options'] ?? []);
                } catch (\LogicException $e) {
                    throw new \LogicException(\sprintf('Invalid mapping for %s::$%s (%s).', $metadata->name, $field, $e->getMessage()), previous: $e);
                }
            }

            $collection->mappings[$field] = $mapping;
        }

        // "virtual" columns
        foreach (self::propertiesFor($metadata->getReflectionClass()) as $property) {
            if (isset($metadata->fieldMappings[$property->name])) {
                continue;
            }

            if (!$mapping = ($property->getAttributes(Mapping::class)[0] ?? null)?->newInstance()) {
                continue;
            }

            if (!$mapping->namer) {
                throw new \LogicException(\sprintf('Invalid virtual mapping for %s::$%s (A namer is required).', $metadata->name, $property->name));
            }

            $collection->virtualMappings[$property->name] = [self::determineVirtualClass($property), $mapping];
        }

        if (!$collection->mappings && !$collection->virtualMappings) {
            return;
        }

        $metadata->table['options'][self::OPTION_KEY] = $collection;
    }

    /**
     * @return class-string<LazyFile>
     */
    private static function determineVirtualClass(\ReflectionProperty $property): string
    {
        $type = $property->getType();

        if ($type instanceof \ReflectionNamedType && Image::class === $type->getName()) {
            return LazyImage::class;
        }

        return LazyFile::class;
    }

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return \ReflectionProperty[]
     */
    private static function propertiesFor(\ReflectionClass $class): iterable
    {
        do {
            foreach ($class->getProperties() as $property) {
                yield $property;
            }
        } while ($class = $class->getParentClass());
    }
}
