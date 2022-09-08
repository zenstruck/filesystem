<?php

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait IsLazyNode
{
    public function __construct(private string $path, private ?Filesystem $filesystem = null)
    {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function setFilesystem(Filesystem $filesystem): void
    {
        $this->filesystem = $filesystem;
    }

    private function filesystem(): Filesystem
    {
        return $this->filesystem ?? throw new \LogicException('The filesystem has not been set.');
    }
}
