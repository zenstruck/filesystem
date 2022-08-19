<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs as ORMPreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\ObjectReflector;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\LazyFile;
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
    /** @var callable[] */
    private array $pendingOperations = [];

    /**
     * @param array<string,bool> $globalConfig
     */
    public function __construct(
        private NodeConfigProvider $configProvider,
        private MultiFilesystem $filesystem,
        private array $globalConfig,
        private ContainerInterface $namers,
    ) {
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
            $node = $ref->get($property);

            if (!$node instanceof Node) {
                continue;
            }

            $this->filesystem->get($config['filesystem'])->delete($node->path());
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function prePersist(LifecycleEventArgs $event): void
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
            $node = $ref->get($property);

            if (!$node instanceof PendingFile) {
                continue;
            }

            $filesystem = $this->filesystem->get($config['filesystem']);

            $ref->set($property, new LazyFile(
                $name = $this->generateName($node, $object, $config),
                $filesystem
            ));

            $this->pendingOperations[] = static function() use ($filesystem, $name, $node) {
                $filesystem->write($name, $node);
            };
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

        foreach ($configs as $property => $config) {
            if (!$event->hasChangedField($config['property'])) {
                continue;
            }

            $old = $event->getOldValue($config['property']);
            $new = $event->getNewValue($config['property']);

            if (!$new instanceof Node && $old instanceof Node && $this->isEnabled(NodeConfigProvider::DELETE_ON_UPDATE, $config)) {
                // user removed node, delete file
                $this->pendingOperations[] = fn() => $this->filesystem->get($config['filesystem'])->delete($old);

                continue;
            }

            if ($new instanceof PendingFile && $this->isEnabled(NodeConfigProvider::WRITE_ON_UPDATE, $config)) {
                // user is adding a new file
                $name = $this->generateName($new, $object, $config);
                $filesystem = $this->filesystem->get($config['filesystem']);

                $event->setNewValue($property, new LazyFile($name, $filesystem));

                $this->pendingOperations[] = static function() use ($name, $filesystem, $new) {
                    $filesystem->write($name, $new);
                };
            }

            if ($old instanceof Node && $new instanceof Node && $old->path() !== $new->path() && $this->isEnabled(NodeConfigProvider::DELETE_ON_UPDATE, $config)) {
                // delete old
                $this->pendingOperations[] = fn() => $this->filesystem->get($config['filesystem'])->delete($old->path());
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        foreach ($this->pendingOperations as $operation) {
            $operation();
        }

        $this->pendingOperations = [];
    }

    /**
     * @param ConfigMapping $config
     */
    private function isEnabled(string $feature, array $config): bool
    {
        return $config[$feature] ?? $this->globalConfig[$feature] ?? true; // @phpstan-ignore-line
    }

    /**
     * @param ConfigMapping $defaultConfig
     */
    private function generateName(PendingFile $file, object $object, array $defaultConfig): string
    {
        $config = $file->config();

        if (\is_callable($config)) {
            return $config($file, $object);
        }

        $name = $config['namer'] ?? $defaultConfig['namer'] ?? 'expression';

        return $this->namer($name)->generateName($file, $object, \array_merge($defaultConfig, $config));
    }

    private function namer(string $name): Namer
    {
        try {
            return $this->namers->get($name);
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException(\sprintf('Namer "%s" is not registered.', $name), previous: $e);
        }
    }
}
