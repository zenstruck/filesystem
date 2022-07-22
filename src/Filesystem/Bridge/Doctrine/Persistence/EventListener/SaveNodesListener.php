<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node\PendingNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class SaveNodesListener
{
    public function __construct(private NodeConfigProvider $configProvider, private MultiFilesystem $filesystem)
    {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postPersist(LifecycleEventArgs $event): void
    {
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

            // TODO PathNamer interface with ExpressionLanguagePathNamer, SlugPathNamer
            // TODO slugify by default
            // TODO $config['path_expression'] for expression language with slug(), file and object
            $node = $this->filesystem->get($config['filesystem'])->write($node->localFile()->getFilename(), $node->localFile());

            $refProp->setValue($object, $node);
        }
    }
}
