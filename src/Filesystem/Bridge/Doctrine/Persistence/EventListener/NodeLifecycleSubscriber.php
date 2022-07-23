<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\EventListener;

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
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\PendingNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeLifecycleSubscriber
{
    /**
     * @param ContainerInterface|array<string,Namer>|null $namers
     */
    public function __construct(
        private NodeConfigProvider $configProvider,
        private MultiFilesystem $filesystem,
        private ContainerInterface|array|null $namers,
    ) {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postRemove(LifecycleEventArgs $event): void
    {
        // todo collections

        if (!$configs = $this->configProvider->configFor($event->getObject()::class)) {
            return;
        }

        $refObj = new \ReflectionObject($object = $event->getObject());

        foreach ($configs as $config) {
            // todo embedded?
            // todo duplicated in ObjectNodeLoader
            if (!($config['delete_on_remove'] ?? true)) {
                continue;
            }

            $refProp = $refObj->getProperty($config['property']);
            $refProp->setAccessible(true);

            if (!$refProp->isInitialized($object)) {
                continue;
            }

            $node = $refProp->getValue($object);

            if (!$node instanceof Node) {
                continue;
            }

            $this->filesystem->get($config['filesystem'])->delete($node->path());
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postPersist(LifecycleEventArgs $event): void
    {
        // todo collections

        if (!$configs = $this->configProvider->configFor($event->getObject()::class)) {
            return;
        }

        $refObj = new \ReflectionObject($object = $event->getObject());

        foreach ($configs as $config) {
            // todo embedded?
            // todo duplicated in ObjectNodeLoader
            $refProp = $refObj->getProperty($config['property']);
            $refProp->setAccessible(true);

            if (!$refProp->isInitialized($object)) {
                continue;
            }

            $node = $refProp->getValue($object);

            if (!$node instanceof PendingNode) {
                continue;
            }

            $node = $this->filesystem->get($config['filesystem'])->write(
                $this->namer($config['namer'] ?? null)->generateName($node, $object, $config),
                $node->localFile()
            );

            $refProp->setValue($object, $node);
        }
    }

    /**
     * @param PreUpdateEventArgs<ObjectManager>|ORMPreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs|ORMPreUpdateEventArgs $event): void
    {
        // todo collections
        // TODO save pending updates and execute in postUpdate/postFlush?

        if (!$configs = $this->configProvider->configFor($event->getObject()::class)) {
            return;
        }

        $refObj = new \ReflectionObject($object = $event->getObject());

        foreach ($configs as $config) {
            if (!$event->hasChangedField($config['property'])) {
                continue;
            }

            // todo embedded?
            // todo duplicated in ObjectNodeLoader
            $refProp = $refObj->getProperty($config['property']);
            $refProp->setAccessible(true);

            if (!$refProp->isInitialized($object)) {
                continue;
            }

            $old = $event->getOldValue($config['property']);
            $new = $event->getNewValue($config['property']);

            if (!$new instanceof Node && $old instanceof Node) {
                // user removed node, delete file
                $this->filesystem->get($config['filesystem'])->delete($old);

                continue;
            }

            if ($new instanceof PendingNode && $new instanceof Node) {
                // user is adding a new file
                $new = $this->filesystem->get($config['filesystem'])->write(
                    $this->namer($config['namer'] ?? null)->generateName($new, $object, $config),
                    $new->localFile()
                );

                $refProp->setValue($object, $new);
            }

            if ($old instanceof Node && $new instanceof Node && $old->path() !== $new->path()) {
                // delete old
                $this->filesystem->get($config['filesystem'])->delete($old->path());
            }
        }
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
}
