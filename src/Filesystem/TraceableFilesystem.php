<?php

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class TraceableFilesystem implements Filesystem
{
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
        $ret = $this->inner->node($path);

        $this->operations[Operation::READ][] = [$path, $ret::class];

        return $ret;
    }

    public function file(string $path): File
    {
        $ret = $this->inner->file($path);

        $this->operations[Operation::READ][] = [$path, $ret::class];

        return $ret;
    }

    public function directory(string $path): Directory
    {
        $ret = $this->inner->directory($path);

        $this->operations[Operation::READ][] = [$path, $ret::class];

        return $ret;
    }

    public function image(string $path, array $config = []): Image
    {
        $ret = $this->inner->image($path);

        $this->operations[Operation::READ][] = [$path, $ret::class];

        return $ret;
    }

    public function has(string $path): bool
    {
        $ret = $this->inner->has($path);

        $this->operations[Operation::READ][] = [$path, null];

        return $ret;
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        $this->inner->copy($source, $destination, $config);

        $this->operations[Operation::COPY][] = [$source, $destination];

        return $this;
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        $this->inner->move($source, $destination, $config);

        $this->operations[Operation::MOVE][] = [$source, $destination];

        return $this;
    }

    public function delete(Directory|string $path, array $config = []): static
    {
        $this->inner->delete($path, $config);

        $this->operations[Operation::DELETE][] = [(string) $path, null];

        return $this;
    }

    public function mkdir(string $path, array $config = []): static
    {
        $this->inner->mkdir($path, $config);

        $this->operations[Operation::MKDIR][] = [$path, null];

        return $this;
    }

    public function chmod(string $path, string $visibility): static
    {
        $this->inner->chmod($path, $visibility);

        $this->operations[Operation::CHMOD][] = [$path, $visibility];

        return $this;
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        $this->inner->write($path, $value, $config);

        $this->operations[Operation::WRITE][] = [$path, \get_debug_type($value)];

        return $this;
    }

    public function last(): File|Directory
    {
        return $this->inner->last();
    }

    public function name(): string
    {
        return $this->inner->name();
    }
}
