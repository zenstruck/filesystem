<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem;

use Symfony\Component\Stopwatch\Stopwatch;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TraceableFilesystem implements Filesystem
{
    use DecoratedFilesystem;

    /** @var array<Operation::*,list<array{0:string,1:string|null,2:float}>> */
    public array $operations = [];

    public function __construct(private Filesystem $inner, private ?Stopwatch $stopwatch = null)
    {
    }

    /**
     * @return array<Operation::*,list<array{0:string,1:string|null,2:float}>>
     */
    public function operations(): array
    {
        return $this->operations;
    }

    /**
     * @return float in milliseconds
     */
    public function totalDuration(): float
    {
        $total = 0;

        foreach ($this->operations as $set) {
            foreach ($set as $data) {
                $total += $data[2];
            }
        }

        return $total;
    }

    public function totalOperations(): int
    {
        return $this->totalReads() + $this->totalWrites();
    }

    public function totalReads(): int
    {
        return \count($this->operations[Operation::READ] ?? []);
    }

    public function totalWrites(): int
    {
        return \array_sum(
            \array_map(fn($type) => \count($this->operations[$type] ?? []), Operation::writes())
        );
    }

    public function reset(): void
    {
        $this->operations = [];
    }

    public function node(string $path): Node
    {
        return $this->track(fn() => $this->inner()->node($path), Operation::READ, $path, 'node');
    }

    public function file(string $path): File
    {
        return $this->track(fn() => $this->inner()->file($path), Operation::READ, $path, 'file');
    }

    public function directory(string $path = ''): Directory
    {
        return $this->track(fn() => $this->inner()->directory($path), Operation::READ, $path, 'dir');
    }

    public function image(string $path): Image
    {
        return $this->track(fn() => $this->inner()->image($path), Operation::READ, $path, 'image');
    }

    public function has(string $path): bool
    {
        return $this->track(fn() => $this->inner()->has($path), Operation::READ, $path);
    }

    public function copy(string $source, string $destination, array $config = []): File
    {
        return $this->track(fn() => $this->inner()->copy($source, $destination, $config), Operation::COPY, $source, $destination);
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        return $this->track(fn() => $this->inner()->move($source, $destination, $config), Operation::MOVE, $source, $destination);
    }

    public function delete(string $path, array $config = []): static
    {
        $this->track(fn() => $this->inner()->delete($path, $config), Operation::DELETE, $path);

        return $this;
    }

    public function mkdir(string $path, Directory|\SplFileInfo|null $content = null, array $config = []): Directory
    {
        return $this->track(fn() => $this->inner()->mkdir($path, $content, $config), Operation::MKDIR, $path);
    }

    public function chmod(string $path, string $visibility): Node
    {
        return $this->track(fn() => $this->inner()->chmod($path, $visibility), Operation::CHMOD, $path, $visibility);
    }

    public function write(string $path, mixed $value, array $config = []): File
    {
        return $this->track(
            fn() => $this->inner()->write($path, $value, $config),
            Operation::WRITE, $path,
            \get_debug_type($value)
        );
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }

    /**
     * @template T
     *
     * @param callable():T $callback
     * @param Operation::* $operation
     */
    private function track(callable $callback, string $operation, string $path, ?string $context = null): mixed
    {
        $start = \microtime(true);
        $event = $this->stopwatch?->start('filesystem.'.$this->name(), 'filesystem');

        try {
            return $callback();
        } finally {
            $event?->stop();
            $this->operations[$operation][] = [$path, $context, (\microtime(true) - $start) * 1000];
        }
    }
}
