<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Fixtures;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\Filesystem\Event\OperationEvent;
use Zenstruck\Filesystem\Event\PostChmodEvent;
use Zenstruck\Filesystem\Event\PostCopyEvent;
use Zenstruck\Filesystem\Event\PostDeleteEvent;
use Zenstruck\Filesystem\Event\PostMkdirEvent;
use Zenstruck\Filesystem\Event\PostMoveEvent;
use Zenstruck\Filesystem\Event\PostWriteEvent;
use Zenstruck\Filesystem\Event\PreChmodEvent;
use Zenstruck\Filesystem\Event\PreCopyEvent;
use Zenstruck\Filesystem\Event\PreDeleteEvent;
use Zenstruck\Filesystem\Event\PreMkdirEvent;
use Zenstruck\Filesystem\Event\PreMoveEvent;
use Zenstruck\Filesystem\Event\PreWriteEvent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FilesystemEventSubscriber implements EventSubscriberInterface
{
    /** @var OperationEvent[] */
    public array $events = [];

    public function on(OperationEvent $event): void
    {
        $this->events[] = $event;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteEvent::class => 'on',
            PostWriteEvent::class => 'on',
            PreCopyEvent::class => 'on',
            PostCopyEvent::class => 'on',
            PreMoveEvent::class => 'on',
            PostMoveEvent::class => 'on',
            PreDeleteEvent::class => 'on',
            PostDeleteEvent::class => 'on',
            PreChmodEvent::class => 'on',
            PostChmodEvent::class => 'on',
            PreMkdirEvent::class => 'on',
            PostMkdirEvent::class => 'on',
        ];
    }
}
