<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\Filesystem\Event\EventDispatcherFilesystem;
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
use Zenstruck\Filesystem\Operation;
use Zenstruck\Tests\FilesystemTest;
use Zenstruck\Tests\Fixtures\FilesystemEventSubscriber;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EventDispatcherFilesystemTest extends FilesystemTest
{
    /**
     * @test
     */
    public function can_track_events(): void
    {
        $dispatcher = self::eventDispatcher();
        $dispatcher->addSubscriber($subscriber = new FilesystemEventSubscriber());

        $filesystem = $this->createFilesystem($dispatcher, [
            Operation::WRITE => true,
            Operation::COPY => true,
            Operation::MOVE => true,
            Operation::DELETE => true,
            Operation::CHMOD => true,
            Operation::MKDIR => true,
        ]);

        $filesystem->write('foo', 'bar');

        $this->assertInstanceOf(PreWriteEvent::class, $subscriber->events[0]);
        $this->assertSame('default', $subscriber->events[0]->filesystem()->name());
        $this->assertSame('foo', $subscriber->events[0]->path);
        $this->assertSame('bar', $subscriber->events[0]->value);

        $this->assertInstanceOf(PostWriteEvent::class, $subscriber->events[1]);
        $this->assertSame('default', $subscriber->events[1]->filesystem()->name());
        $this->assertSame('foo', $subscriber->events[1]->path());
        $this->assertSame('bar', $subscriber->events[1]->value());
        $this->assertSame('foo', $subscriber->events[1]->node()->path()->toString());

        $filesystem->mkdir('bar');

        $this->assertInstanceOf(PreMkdirEvent::class, $subscriber->events[2]);
        $this->assertSame('default', $subscriber->events[2]->filesystem()->name());
        $this->assertSame('bar', $subscriber->events[2]->path);

        $this->assertInstanceOf(PostMkdirEvent::class, $subscriber->events[3]);
        $this->assertSame('default', $subscriber->events[3]->filesystem()->name());
        $this->assertSame('bar', $subscriber->events[3]->path());
        $this->assertSame('bar', $subscriber->events[3]->directory()->path()->toString());

        $filesystem->chmod('foo', 'public');

        $this->assertInstanceOf(PreChmodEvent::class, $subscriber->events[4]);
        $this->assertSame('default', $subscriber->events[4]->filesystem()->name());
        $this->assertSame('foo', $subscriber->events[4]->path);
        $this->assertSame('public', $subscriber->events[4]->visibility);

        $this->assertInstanceOf(PostChmodEvent::class, $subscriber->events[5]);
        $this->assertSame('default', $subscriber->events[5]->filesystem()->name());
        $this->assertSame('foo', $subscriber->events[5]->path());
        $this->assertSame('public', $subscriber->events[5]->visibility());
        $this->assertSame('foo', $subscriber->events[5]->node()->path()->toString());

        $filesystem->copy('foo', 'file.png');

        $this->assertInstanceOf(PreCopyEvent::class, $subscriber->events[6]);
        $this->assertSame('default', $subscriber->events[6]->filesystem()->name());
        $this->assertSame('foo', $subscriber->events[6]->source);
        $this->assertSame('file.png', $subscriber->events[6]->destination);

        $this->assertInstanceOf(PostCopyEvent::class, $subscriber->events[7]);
        $this->assertSame('default', $subscriber->events[7]->filesystem()->name());
        $this->assertSame('foo', $subscriber->events[7]->source());
        $this->assertSame('file.png', $subscriber->events[7]->destination());
        $this->assertSame('foo', $subscriber->events[7]->sourceNode()->path()->toString());
        $this->assertSame('file.png', $subscriber->events[7]->destinationNode()->path()->toString());

        $filesystem->delete('foo');

        $this->assertInstanceOf(PreDeleteEvent::class, $subscriber->events[8]);
        $this->assertSame('default', $subscriber->events[8]->filesystem()->name());
        $this->assertSame('foo', $subscriber->events[8]->path);

        $this->assertInstanceOf(PostDeleteEvent::class, $subscriber->events[9]);
        $this->assertSame('default', $subscriber->events[9]->filesystem()->name());
        $this->assertSame('foo', $subscriber->events[9]->path());

        $filesystem->move('file.png', 'file2.png');

        $this->assertInstanceOf(PreMoveEvent::class, $subscriber->events[10]);
        $this->assertSame('default', $subscriber->events[10]->filesystem()->name());
        $this->assertSame('file.png', $subscriber->events[10]->source);
        $this->assertSame('file2.png', $subscriber->events[10]->destination);

        $this->assertInstanceOf(PostMoveEvent::class, $subscriber->events[11]);
        $this->assertSame('default', $subscriber->events[11]->filesystem()->name());
        $this->assertSame('file.png', $subscriber->events[11]->source());
        $this->assertSame('file2.png', $subscriber->events[11]->destination());
        $this->assertSame('file2.png', $subscriber->events[11]->destinationNode()->path()->toString());

        $this->assertCount(12, $subscriber->events);
    }

    protected function createFilesystem(?EventDispatcherInterface $dispatcher = null, array $config = []): EventDispatcherFilesystem
    {
        return new EventDispatcherFilesystem(
            in_memory_filesystem(),
            $dispatcher ?? self::eventDispatcher(),
            $config,
        );
    }

    private static function eventDispatcher(): EventDispatcher
    {
        return new EventDispatcher();
    }
}
