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
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Zenstruck\Filesystem\Doctrine\Mapping\HasFiles;
use Zenstruck\Filesystem\Doctrine\Mapping\Stateful;
use Zenstruck\Filesystem\Doctrine\Mapping\Stateless;
use Zenstruck\Filesystem\Doctrine\Mapping\StoreAsDsn;
use Zenstruck\Filesystem\Doctrine\Mapping\StoreAsPath;
use Zenstruck\Filesystem\Doctrine\Mapping\StoreWithMetadata;
use Zenstruck\Filesystem\Doctrine\Types\FileDsnType;
use Zenstruck\Filesystem\Doctrine\Types\FileMetadataType;
use Zenstruck\Filesystem\Doctrine\Types\FilePathType;
use Zenstruck\Filesystem\Doctrine\Types\ImageDsnType;
use Zenstruck\Filesystem\Doctrine\Types\ImageMetadataType;
use Zenstruck\Filesystem\Doctrine\Types\ImagePathType;
use Zenstruck\Filesystem\Exception\UnregisteredFilesystem;
use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\LazyDirectory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Filesystem\Node\Path\Namer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class NodeMappingListener
{
    public const OPTION_KEY = '_zsfs';

    public function __construct(private FilesystemRegistry $filesystems, private ContainerInterface $pathGenerators)
    {
    }

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

        foreach (self::mappedPropertiesFor($class) as [$property, $mapping]) {
            $this->ensureFilesystemExists($mapping->filesystem(), $property);
            $this->ensurePathGeneratorExists($mapping->namer(), $property);

            $type = $property->getType();

            if (!$type instanceof \ReflectionNamedType) {
                throw new \LogicException(\sprintf('Property "%s::$%s" must have a typehint and not be a union/intersection.', $property->class, $property->name));
            }

            $nodeClass = $type->getName();

            if ($mapping instanceof Stateless) {
                $collection->statelessMappings[$property->name] = [
                    match (true) {
                        Image::class === $nodeClass => LazyImage::class,
                        File::class === $nodeClass => LazyFile::class,
                        Directory::class === $nodeClass => LazyDirectory::class,
                        default => throw new \LogicException(\sprintf('Property "%s::$%s" must have a "%s", "%s" or "%s" typehint.', $property->class, $property->name, File::class, Directory::class, Image::class)),
                    },
                    $mapping,
                ];

                continue;
            }

            if (!$mapping instanceof Stateful) {
                throw new MappingException(\sprintf('Unknown mapping type "%s" for %s::$%s.', $mapping::class, $class->name, $property->name));
            }

            if (!\in_array($nodeClass, [File::class, Image::class])) {
                throw new \LogicException(\sprintf('Property "%s::$%s" must have a "%s" or "%s" typehint.', $property->class, $property->name, File::class, Image::class));
            }

            $mapping->validate($nodeClass); // @phpstan-ignore-line

            // cast to object since array in orm v2 and object in orm v3
            if ($metadata->hasField($property->name) && isset(((object) $metadata->getFieldMapping($property->name))->declared)) {
                // using inheritance mapping - field already mapped on parent
                $collection->statefulMappings[$property->name] = $mapping;

                continue;
            }

            if ($metadata->hasField($property->name)) {
                throw new MappingException(\sprintf('Cannot use zenstruck/filesystem mapping with doctrine/orm mapping for %s::$%s. Use %s::$column to customize the mapping.', $class->name, $property->name, $mapping::class));
            }

            $fieldMapping = $mapping->column;

            if (isset($fieldMapping['name'])) {
                $fieldMapping['columnName'] = $fieldMapping['name'];
                unset($fieldMapping['name']);
            }

            $metadata->mapField(\array_merge($fieldMapping, [
                'fieldName' => $property->name,
                'type' => self::doctrineTypeFor($mapping, $nodeClass), // @phpstan-ignore-line
                'nullable' => $fieldMapping['nullable'] ?? $type->allowsNull(),
            ]));

            $collection->statefulMappings[$property->name] = $mapping;
        }

        if (!$collection->statelessMappings && !$collection->statefulMappings) {
            return;
        }

        $metadata->table['options'][self::OPTION_KEY] = $collection;
    }

    /**
     * @param class-string<File>|class-string<Image> $nodeClass
     */
    private static function doctrineTypeFor(Stateful $mapping, string $nodeClass): string
    {
        return match ($mapping::class) {
            StoreAsPath::class => File::class === $nodeClass ? FilePathType::NAME : ImagePathType::NAME,
            StoreAsDsn::class => File::class === $nodeClass ? FileDsnType::NAME : ImageDsnType::NAME,
            StoreWithMetadata::class => File::class === $nodeClass ? FileMetadataType::NAME : ImageMetadataType::NAME,
            default => throw new \LogicException('Invalid mapping'),
        };
    }

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return iterable<array{0:\ReflectionProperty,1:Mapping}>
     */
    private static function mappedPropertiesFor(\ReflectionClass $class): iterable
    {
        do {
            foreach ($class->getProperties() as $property) {
                if ($mapping = $property->getAttributes(Mapping::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null) {
                    yield [$property, $mapping->newInstance()];
                }
            }
        } while ($class = $class->getParentClass());
    }

    private function ensurePathGeneratorExists(?Namer $namer, \ReflectionProperty $property): void
    {
        if (!$namer) {
            return;
        }

        if (!$this->pathGenerators->has($namer->id())) {
            throw new \LogicException(\sprintf('Property "%s::$%s" configured a path generator ("%s") that does not exist.', $property->class, $property->name, $namer->id()));
        }
    }

    private function ensureFilesystemExists(?string $filesystem, \ReflectionProperty $property): void
    {
        if (!$filesystem) {
            return;
        }

        try {
            $this->filesystems->get($filesystem);
        } catch (UnregisteredFilesystem $e) {
            throw new \LogicException(\sprintf('Property "%s::$%s" configured a filesystem ("%s") that does not exist.', $property->class, $property->name, $filesystem), previous: $e);
        }
    }
}
