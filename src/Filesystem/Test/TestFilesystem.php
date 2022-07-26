<?php

namespace Zenstruck\Filesystem\Test;

use Zenstruck\Assert;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Test\Node\TestDirectory;
use Zenstruck\Filesystem\Test\Node\TestFile;
use Zenstruck\Filesystem\Test\Node\TestImage;
use Zenstruck\Filesystem\WrappedFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestFilesystem implements Filesystem
{
    use WrappedFilesystem;

    public function __construct(private Filesystem $inner)
    {
    }

    /**
     * @param null|callable(TestFile|TestDirectory<Node>):void $callback
     */
    public function assertExists(string $path, ?callable $callback = null): self
    {
        $node = Assert::try(fn() => $this->node($path), 'Node at path "%s" does not exist.', [$path]);

        if ($callback) {
            $callback($node); // @phpstan-ignore-line
        }

        return $this;
    }

    public function assertNotExists(string $path): self
    {
        Assert::false($this->exists($path), 'Node at path "%s" exists but it should not.', [$path]);

        return $this;
    }

    /**
     * @param null|callable(TestFile):void $callback
     */
    public function assertFileExists(string $path, ?callable $callback = null): self
    {
        $node = Assert::try(fn() => $this->file($path), 'File at path "%s" does not exist.', [$path]);

        if ($callback) {
            $callback($node); // @phpstan-ignore-line
        }

        return $this;
    }

    /**
     * @param null|callable(TestImage):void $callback
     */
    public function assertImageExists(string $path, ?callable $callback = null): self
    {
        $node = Assert::try(fn() => $this->image($path), 'Image at path "%s" does not exist.', [$path]);

        if ($callback) {
            $callback($node); // @phpstan-ignore-line
        }

        return $this;
    }

    /**
     * @param null|callable(TestDirectory<Node>):void $callback
     */
    public function assertDirectoryExists(string $path, ?callable $callback = null): self
    {
        $node = Assert::try(fn() => $this->directory($path), 'Directory at path "%s" does not exist.', [$path]);

        if ($callback) {
            $callback($node); // @phpstan-ignore-line
        }

        return $this;
    }

    /**
     * Assert the contents of two files match.
     */
    public function assertSame(string $path1, string $path2): self
    {
        $first = Assert::try(fn() => $this->file($path1), 'File at path "%s" does not exist.', [$path1]);
        $second = Assert::try(fn() => $this->file($path1), 'File at path "%s" does not exist.', [$path2]);

        Assert::that($first->contents())->is($second->contents());

        return $this;
    }

    /**
     * Assert the contents of two files do not match.
     */
    public function assertNotSame(string $path1, string $path2): self
    {
        $first = Assert::try(fn() => $this->file($path1), 'File at path "%s" does not exist.', [$path1]);
        $second = Assert::try(fn() => $this->file($path2), 'File at path "%s" does not exist.', [$path2]);

        Assert::that($first->contents())->isNot($second->contents());

        return $this;
    }

    public function node(string $path): File|Directory
    {
        $node = $this->inner()->node($path);

        return $node instanceof File ? new TestFile($node) : new TestDirectory($node);
    }

    public function file(string $path): File
    {
        return new TestFile($this->inner()->file($path));
    }

    public function image(string $path, array $config = []): Image
    {
        return new TestImage($this->inner()->image($path));
    }

    public function directory(string $path): Directory
    {
        return new TestDirectory($this->inner()->directory($path));
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }
}
