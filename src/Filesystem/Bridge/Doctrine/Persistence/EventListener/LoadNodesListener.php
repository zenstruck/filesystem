<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Filesystem\Bridge\Doctrine\ObjectNodeLoader;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class LoadNodesListener
{
    public function __construct(private ObjectNodeLoader $nodeLoader)
    {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postLoad(LifecycleEventArgs $event): void
    {
        $this->nodeLoader->load($event->getObject());
    }
}
