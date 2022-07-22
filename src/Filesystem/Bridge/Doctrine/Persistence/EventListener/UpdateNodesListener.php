<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs as ORMPreUpdateEventArgs;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\PendingNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UpdateNodesListener
{
    public function __construct(private NodeConfigProvider $configProvider, private MultiFilesystem $filesystem)
    {
    }

    /**
     * @param PreUpdateEventArgs<ObjectManager>|ORMPreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs|ORMPreUpdateEventArgs $event): void
    {
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

            if ($new instanceof PendingNode) {
                // TODO PathNamer interface with ExpressionLanguagePathNamer, SlugPathNamer
                // TODO slugify by default
                // TODO $config['path_expression'] for expression language with slug(), file and object
                $new = $this->filesystem->get($config['filesystem'])->write($new->localFile()->getFilename(), $new->localFile());

                $refProp->setValue($object, $new);
            }

            if ($old instanceof Node && $new instanceof Node && $old->path() !== $new->path()) {
                // delete old
                $this->filesystem->get($config['filesystem'])->delete($old->path());
            }
        }
    }
}
