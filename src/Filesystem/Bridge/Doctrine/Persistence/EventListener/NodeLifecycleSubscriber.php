<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs as ORMPreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\ChecksumNamer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\ExpressionNamer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\SlugifyNamer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\ObjectReflector;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\FileCollection;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type ConfigMapping from NodeConfigProvider
 */
final class NodeLifecycleSubscriber
{
    /** @var \WeakMap<object,callable[]> */
    private \WeakMap $pendingRecomputeOperations;

    /** @var callable[] */
    private array $pendingOperations = [];

    /**
     * @param ContainerInterface|array<string,Namer> $namers
     * @param array<string,bool>                     $globalConfig
     */
    public function __construct(
        private NodeConfigProvider $configProvider,
        private MultiFilesystem $filesystem,
        private array $globalConfig = [],
        private ContainerInterface|array|null $namers = null,
    ) {
        $this->pendingRecomputeOperations = new \WeakMap();
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postLoad(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if (!$configs = $this->configProvider->configFor($object::class)) {
            return;
        }

        $ref = null;

        foreach ($configs as $property => $config) {
            if (!$this->isEnabled(NodeConfigProvider::AUTOLOAD, $config)) {
                continue;
            }

            $ref ??= new ObjectReflector($object, $configs);

            $ref->load($this->filesystem, $property);
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postRemove(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if (!$configs = $this->configProvider->configFor($object::class)) {
            return;
        }

        $ref = null;

        foreach ($configs as $property => $config) {
            if (!$this->isEnabled(NodeConfigProvider::DELETE_ON_REMOVE, $config)) {
                continue;
            }

            $ref ??= new ObjectReflector($object, $configs);

            if (!$item = $ref->get($property)) {
                continue;
            }

            $nodes = $item instanceof Node ? [$item] : $item->all();

            foreach ($nodes as $node) {
                $this->filesystem->get($config['filesystem'])->delete($node->path());
            }
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postPersist(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if (!$configs = $this->configProvider->configFor($object::class)) {
            return;
        }

        $ref = null;

        foreach ($configs as $property => $config) {
            if (!$this->isEnabled(NodeConfigProvider::WRITE_ON_PERSIST, $config)) {
                continue;
            }

            $ref ??= new ObjectReflector($object, $configs);
            $item = $ref->get($property);

            if ($item instanceof PendingFile) {
                $item = $this->filesystem->get($config['filesystem'])->write(
                    $this->namer($config['namer'] ?? null)->generateName($item, $object, $config),
                    $item->localFile()
                );

                $ref->set($property, $item->last()->ensureFile());

                $this->addPendingRecomputeOperation($object, fn() => null);

                continue;
            }

            if (!$item instanceof FileCollection) {
                continue;
            }

            $files = [];

            foreach ($item as $file) {
                if ($file instanceof PendingFile) {
                    $file = $this->filesystem->get($config['filesystem'])->write(
                        $this->namer($config['namer'] ?? null)->generateName($file, $object, $config),
                        $file->localFile()
                    )->last()->ensureFile();
                }

                $files[] = $file;
            }

            $ref->set($property, new FileCollection($files));

            $this->addPendingRecomputeOperation($object, fn() => null);
        }
    }

    /**
     * @param PreUpdateEventArgs<ObjectManager>|ORMPreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs|ORMPreUpdateEventArgs $event): void
    {
        $object = $event->getObject();

        if (!$configs = $this->configProvider->configFor($object::class)) {
            return;
        }

        $ref = null;

        foreach ($configs as $property => $config) {
            if (!$event->hasChangedField($config['property'])) {
                continue;
            }

            $old = $event->getOldValue($config['property']);
            $new = $event->getNewValue($config['property']);

            if (null === $new && $old instanceof FileCollection && $this->isEnabled(NodeConfigProvider::DELETE_ON_UPDATE, $config)) {
                // user removed collection, delete files
                foreach ($old as $file) {
                    $this->pendingOperations[] = fn() => $this->filesystem->get($config['filesystem'])->delete($file);
                }

                continue;
            }

            if (!$new instanceof Node && $old instanceof Node && $this->isEnabled(NodeConfigProvider::DELETE_ON_UPDATE, $config)) {
                // user removed node, delete file
                $this->pendingOperations[] = fn() => $this->filesystem->get($config['filesystem'])->delete($old);

                continue;
            }

            $ref ??= new ObjectReflector($object, $configs);

            if ($new instanceof PendingFile && $this->isEnabled(NodeConfigProvider::WRITE_ON_UPDATE, $config)) {
                // user is adding a new file
                $this->addPendingRecomputeOperation($object, function() use ($config, $object, $new, $property, $ref) {
                    $new = $this->filesystem->get($config['filesystem'])->write(
                        $this->namer($config['namer'] ?? null)->generateName($new, $object, $config),
                        $new->localFile()
                    );

                    $ref->set($property, $new->last()->ensureFile());
                });
            }

            if ($old instanceof Node && $new instanceof Node && $old->path() !== $new->path() && $this->isEnabled(NodeConfigProvider::DELETE_ON_UPDATE, $config)) {
                // delete old
                $this->pendingOperations[] = fn() => $this->filesystem->get($config['filesystem'])->delete($old->path());
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        foreach ($this->pendingRecomputeOperations as $object => $operations) {
            foreach ($operations as $operation) {
                $operation();
            }

            self::clearChangeSet($object, $event->getEntityManager());
        }

        foreach ($this->pendingOperations as $operation) {
            $operation();
        }

        $this->pendingRecomputeOperations = new \WeakMap();
    }

    private function addPendingRecomputeOperation(object $object, callable $callback): void
    {
        if (!isset($this->pendingRecomputeOperations[$object])) {
            $this->pendingRecomputeOperations[$object] = [];
        }

        $this->pendingRecomputeOperations[$object][] = $callback;
    }

    /**
     * @param ConfigMapping $config
     */
    private function isEnabled(string $feature, array $config): bool
    {
        return $config[$feature] ?? $this->globalConfig[$feature] ?? true; // @phpstan-ignore-line
    }

    private function namer(?string $name): Namer
    {
        $name ??= 'slugify';

        if (null === $this->namers) {
            $this->namers = [
                'slugify' => new SlugifyNamer(),
                'checksum' => new ChecksumNamer(),
                'expression' => new ExpressionNamer(),
            ];
        }

        if ($this->namers instanceof ContainerInterface) {
            try {
                return $this->namers->get($name);
            } catch (NotFoundExceptionInterface $e) {
            }
        }

        if (\is_array($this->namers) && \array_key_exists($name, $this->namers)) {
            return $this->namers[$name];
        }

        throw new \LogicException(\sprintf('Namer "%s" is not registered.', $name), previous: $e ?? null);
    }

    /**
     * We need to clear changes as swapping the Node objects triggers a "change".
     *
     * TODO: I think this can be improved with another event (prePersist) and calculate
     */
    private static function clearChangeSet(object $object, ObjectManager $om): void
    {
        if (!$om instanceof EntityManagerInterface) {
            return;
        }

        $uow = $om->getUnitOfWork();
        $uow->computeChangeSet($om->getClassMetadata($object::class), $object);
        $uow->clearEntityChangeSet(\spl_object_id($object));
    }
}
