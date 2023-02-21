<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\DecoratedFilesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Operation;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EventDispatcherFilesystem implements Filesystem
{
    use DecoratedFilesystem;

    /**
     * @param array<Operation::*,bool> $config
     */
    public function __construct(
        private Filesystem $inner,
        private EventDispatcherInterface $dispatcher,
        private array $config = [],
    ) {
    }

    public function copy(string $source, string $destination, array $config = []): File
    {
        $this->dispatch($event = new PreCopyEvent($this, $source, $destination, $config), Operation::COPY);
        $file = $this->inner->copy($event->source, $event->destination, $event->config);
        $this->dispatch(new PostCopyEvent($event), Operation::COPY);

        return $file;
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        $this->dispatch($event = new PreMoveEvent($this, $source, $destination, $config), Operation::MOVE);
        $file = $this->inner->move($event->source, $event->destination, $event->config);
        $this->dispatch(new PostMoveEvent($event), Operation::MOVE);

        return $file;
    }

    public function delete(string $path, array $config = []): static
    {
        $this->dispatch($event = new PreDeleteEvent($this, $path, $config), Operation::DELETE);
        $this->inner->delete($event->path, $event->config);
        $this->dispatch(new PostDeleteEvent($event), Operation::DELETE);

        return $this;
    }

    public function mkdir(string $path, Directory|\SplFileInfo|null $content = null, array $config = []): Directory
    {
        $this->dispatch($event = new PreMkdirEvent($this, $path, $config), Operation::MKDIR);
        $directory = $this->inner->mkdir($event->path, $content, $event->config);
        $this->dispatch(new PostMkdirEvent($event), Operation::MKDIR);

        return $directory;
    }

    public function chmod(string $path, string $visibility): Node
    {
        $this->dispatch($event = new PreChmodEvent($this, $path, $visibility), Operation::CHMOD);
        $node = $this->inner->chmod($event->path, $event->visibility);
        $this->dispatch(new PostChmodEvent($event), Operation::CHMOD);

        return $node;
    }

    public function write(string $path, mixed $value, array $config = []): File
    {
        $this->dispatch($event = new PreWriteEvent($this, $path, $value, $config), Operation::WRITE);
        $file = $this->inner->write($event->path, $event->value, $event->config);
        $this->dispatch(new PostWriteEvent($event), Operation::WRITE);

        return $file;
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }

    private function dispatch(OperationEvent $event, string $operation): void
    {
        if ($this->config[$operation] ?? false) {
            $this->dispatcher->dispatch($event);
        }
    }
}
