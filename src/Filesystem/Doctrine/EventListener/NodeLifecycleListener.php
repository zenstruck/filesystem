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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs as ORMPreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Doctrine\Mapping;
use Zenstruck\Filesystem\Doctrine\Mapping\HasFiles;
use Zenstruck\Filesystem\Doctrine\Mapping\Stateful;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PathGenerator;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class NodeLifecycleListener
{
    /** @var callable[] */
    private array $postFlushOperations = [];

    public function __construct(private ContainerInterface $container)
    {
    }

    public function load(object $object, ObjectManager $om, bool $force): void
    {
        [$object, $metadata, $collection] = self::extract($object, $om);

        if (!$collection) {
            return;
        }

        if (!$collection->autoload && !$force) {
            return;
        }

        // "real" column properties
        foreach ($collection->statefulMappings as $field => $mapping) {
            $file = $metadata->getFieldValue($object, $field);

            if (!$file instanceof LazyNode) {
                continue;
            }

            $file->setFilesystem(fn() => $this->filesystem($mapping));
        }

        // "virtual" column properties
        foreach ($collection->statelessMappings as $field => [$class, $mapping]) {
            $property = self::property($metadata->getReflectionClass(), $field);
            $property->setAccessible(true);

            if ($property->isInitialized($object)) {
                continue;
            }

            $file = new $class();
            $file->setFilesystem(fn() => $this->filesystem($mapping));
            $file->setPath(fn() => $this->generatePath($mapping, $file, $object, $field));

            $property->setValue($object, $file);
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postLoad(LifecycleEventArgs $event): void
    {
        $this->load($event->getObject(), $event->getObjectManager(), force: false);
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postRemove(LifecycleEventArgs $event): void
    {
        [$object, $metadata, $collection] = self::extract($event->getObject(), $event->getObjectManager());

        if (!$collection) {
            return;
        }

        foreach ($collection->statefulMappings as $field => $mapping) {
            if (!$mapping->deleteOnRemove) {
                continue;
            }

            $file = $metadata->getFieldValue($object, $field);

            if (!$file instanceof File) {
                continue;
            }

            $this->filesystem($mapping)->delete($file->path());
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        [$object, $metadata, $collection] = self::extract($event->getObject(), $event->getObjectManager());

        if (!$collection) {
            return;
        }

        foreach ($collection->statefulMappings as $field => $mapping) {
            $file = $metadata->getFieldValue($object, $field);

            if (!$file instanceof PendingFile) {
                continue;
            }

            $metadata->setFieldValue($object, $field, $this->convertPendingFile($mapping, $file, $object, $field));
        }
    }

    /**
     * @param PreUpdateEventArgs<ObjectManager>|ORMPreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs|ORMPreUpdateEventArgs $event): void
    {
        [$object, , $collection] = self::extract($event->getObject(), $event->getObjectManager());

        if (!$collection) {
            return;
        }

        foreach ($collection->statefulMappings as $field => $mapping) {
            if (!$event->hasChangedField($field)) {
                continue;
            }

            $old = $event->getOldValue($field);
            $new = $event->getNewValue($field);

            if ($new instanceof PendingFile) {
                $new = $this->convertPendingFile($mapping, $new, $object, $field);

                $event->setNewValue($field, $new);

                // just setting the new value does not update the property so refresh the object on flush
                $this->postFlushOperations[] = static fn() => $event->getObjectManager()->refresh($object);

                // because the above refresh clears the values, reload them
                // todo is there a better method to do this?
                $this->postFlushOperations[] = fn(EntityManagerInterface $em) => $this->load($object, $em, force: true);
            }

            if (self::shouldOldFileBeRemoved($mapping, $old, $new)) {
                $this->postFlushOperations[] = fn() => $this->filesystem($mapping)->delete($old->path());
            }
        }
    }

    /**
     * @param ManagerEventArgs<EntityManagerInterface> $event
     */
    public function postFlush(ManagerEventArgs $event): void
    {
        foreach ($this->postFlushOperations as $operation) {
            $operation($event->getObjectManager());
        }

        $this->postFlushOperations = [];
    }

    private function convertPendingFile(Mapping $mapping, PendingFile $file, object $object, string $field): LazyFile
    {
        $path = $this->generatePath($mapping, $file, $object, $field);

        $this->postFlushOperations[] = fn() => $this->filesystem($mapping)->write($path, $file);

        $lazyFile = $file instanceof PendingImage ? new LazyImage($path) : new LazyFile($path);
        $lazyFile->setFilesystem($this->filesystem($mapping));

        return $lazyFile;
    }

    private function generatePath(Mapping $mapping, File $file, object $object, string $field): string
    {
        return $this->container->get(PathGenerator::class)->generate(
            $mapping->namer() ?? throw new \LogicException(\sprintf('To save pending files/images, a "namer" must be configured in the filesystem mapping for "%s::$%s".', $object::class, $field)),
            $file,
            ['this' => $object]
        );
    }

    /**
     * @return array{0:object,1:ClassMetadata<object>,2:HasFiles|null}
     */
    private static function extract(object $object, ObjectManager $om): array
    {
        if (!$om instanceof EntityManagerInterface) {
            throw new \LogicException('Only ORM is supported currently.');
        }

        $metadata = $om->getClassMetadata($object::class);
        $collection = $metadata->table['options'][NodeMappingListener::OPTION_KEY] ?? null;

        return [$object, $metadata, $collection];
    }

    private static function shouldOldFileBeRemoved(Stateful $mapping, mixed $old, mixed $new): bool
    {
        if (!$mapping->deleteOnUpdate) {
            return false;
        }

        if (!$old instanceof File) {
            // was set from null to file
            return false;
        }

        if (!$new instanceof File) {
            // was set to null
            return true;
        }

        return $new->path()->toString() !== $old->path()->toString();
    }

    private function filesystem(Mapping $mapping): Filesystem
    {
        // todo filesystem might be null
        return $this->container->get('filesystem_locator')->get($mapping->filesystem());
    }

    /**
     * @param \ReflectionClass<object> $class
     */
    private static function property(\ReflectionClass $class, string $property): \ReflectionProperty
    {
        do {
            try {
                return $class->getProperty($property);
            } catch (\ReflectionException $e) {
            }
        } while ($class = $class->getParentClass());

        throw $e;
    }
}
