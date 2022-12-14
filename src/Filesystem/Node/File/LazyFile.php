<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\DecoratedNode;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class LazyFile implements File
{
    use DecoratedFile, DecoratedNode;

    protected File $inner;
    private ?Filesystem $filesystem = null;

    /** @var Path|string|callable():string */
    private $path;

    /**
     * @param string|callable():string $path
     */
    public function __construct(string|callable $path)
    {
        $this->path = $path;
    }

    public function setFilesystem(Filesystem $filesystem): void
    {
        $this->filesystem = $filesystem;
    }

    public function path(): Path
    {
        if ($this->path instanceof Path) {
            return $this->path;
        }

        if (\is_callable($this->path)) {
            return $this->path = new Path(($this->path)());
        }

        return $this->path = new Path($this->path);
    }

    public function guessExtension(): ?string
    {
        return $this->path()->extension() ?? $this->inner()->guessExtension();
    }

    protected function inner(): File
    {
        return $this->inner ??= $this->filesystem()->file($this->path());
    }

    protected function filesystem(): Filesystem
    {
        return $this->filesystem ?? throw new \RuntimeException('Filesystem not set.');
    }
}
