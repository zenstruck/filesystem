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

    /** @var array<Operation::*,list<array{0:string,1:string|null}>> */
    public array $operations = [];

    public function __construct(private Filesystem $inner)
    {
    }

    /**
     * @return array<Operation::*,list<array{0:string,1:string|null}>>
     */
    public function operations(): array
    {
        return $this->operations;
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

    public function node(string $path): File|Directory
    {
        $this->operations[Operation::READ][] = [$path, 'node'];

        return $this->inner()->node($path);
    }

    public function file(string $path): File
    {
        $this->operations[Operation::READ][] = [$path, 'file'];

        return $this->inner()->file($path);
    }

    public function directory(string $path = ''): Directory
    {
        $this->operations[Operation::READ][] = [$path, 'dir'];

        return $this->inner()->directory($path);
    }

    public function image(string $path): Image
    {
        $this->operations[Operation::READ][] = [$path, 'image'];

        return $this->inner()->image($path);
    }

    public function has(string $path): bool
    {
        $this->operations[Operation::READ][] = [$path, null];

        return $this->inner()->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        $this->operations[Operation::COPY][] = [$source, $destination];

        $this->inner()->copy($source, $destination, $config);

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        $this->operations[Operation::MOVE][] = [$source, $destination];

        $this->inner()->move($source, $destination, $config);

        return $this;
    }

    public function delete(Directory|string $path, array $config = []): static
    {
        $this->operations[Operation::DELETE][] = [$path instanceof Directory ? $path->path() : $path, null];

        $this->inner()->delete($path, $config);

        return $this;
    }

    public function mkdir(string $path, array $config = []): static
    {
        $this->operations[Operation::MKDIR][] = [$path, null];

        $this->inner()->mkdir($path, $config);

        return $this;
    }

    public function chmod(string $path, string $visibility): static
    {
        $this->operations[Operation::CHMOD][] = [$path, $visibility];

        $this->inner()->chmod($path, $visibility);

        return $this;
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        $this->operations[Operation::WRITE][] = [$path, \get_debug_type($value)];

        $this->inner()->write($path, $value, $config);

        return $this;
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }
}
