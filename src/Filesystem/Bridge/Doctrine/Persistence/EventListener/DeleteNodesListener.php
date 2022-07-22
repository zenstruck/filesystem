<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class DeleteNodesListener
{
    public function __construct(private NodeConfigProvider $configProvider, private MultiFilesystem $filesystem)
    {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postRemove(LifecycleEventArgs $event): void
    {
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
}
