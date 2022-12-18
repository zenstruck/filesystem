<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Test;

use Zenstruck\Assert;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\DecoratedFilesystem;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Test\Node\TestDirectory;
use Zenstruck\Filesystem\Test\Node\TestFile;
use Zenstruck\Filesystem\Test\Node\TestImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestFilesystem implements Filesystem
{
    use DecoratedFilesystem;

    public function __construct(private Filesystem $inner)
    {
        if (!\class_exists(Assert::class)) {
            throw new \LogicException('zenstruck/assert is required to use the test filesystem. Install with "composer require --dev zenstruck/assert".');
        }
    }

    /**
     * @param null|callable(TestFile|TestDirectory):void $callback
     */
    public function assertExists(string $path, ?callable $callback = null): self
    {
        $node = Assert::try(fn() => $this->node($path), 'Node at path "{path}" does not exist.', ['path' => $path]);

        if ($callback) {
            $callback($node);
        }

        return $this;
    }

    public function assertNotExists(string $path): self
    {
        Assert::false($this->has($path), 'Node at path "{path}" exists but it should not.', ['path' => $path]);

        return $this;
    }

    /**
     * @param null|callable(TestFile):void $callback
     */
    public function assertFileExists(string $path, ?callable $callback = null): self
    {
        $node = Assert::try(fn() => $this->file($path), 'File at path "{path}" does not exist.', ['path' => $path]);

        if ($callback) {
            $callback($node);
        }

        return $this;
    }

    /**
     * @param null|callable(TestImage):void $callback
     */
    public function assertImageExists(string $path, ?callable $callback = null): self
    {
        $node = Assert::try(fn() => $this->image($path), 'Image at path "{path}" does not exist.', ['path' => $path]);

        if ($callback) {
            $callback($node);
        }

        return $this;
    }

    /**
     * @param null|callable(TestDirectory):void $callback
     */
    public function assertDirectoryExists(string $path, ?callable $callback = null): self
    {
        $node = Assert::try(fn() => $this->directory($path), 'Directory at path "{path}" does not exist.', ['path' => $path]);

        if ($callback) {
            $callback($node);
        }

        return $this;
    }

    /**
     * Assert the contents of two files match.
     */
    public function assertSame(string $path1, string $path2): self
    {
        $first = Assert::try(fn() => $this->file($path1), 'File at path "{path} does not exist.', ['path' => $path1]);
        $second = Assert::try(fn() => $this->file($path1), 'File at path "{path}" does not exist.', ['path' => $path2]);

        Assert::that($first->contents())->is($second->contents());

        return $this;
    }

    /**
     * Assert the contents of two files do not match.
     */
    public function assertNotSame(string $path1, string $path2): self
    {
        $first = Assert::try(fn() => $this->file($path1), 'File at path "{path} does not exist.', ['path' => $path1]);
        $second = Assert::try(fn() => $this->file($path2), 'File at path "{path}" does not exist.', ['path' => $path2]);

        Assert::that($first->contents())->isNot($second->contents());

        return $this;
    }

    public function node(string $path): TestFile|TestDirectory
    {
        $node = $this->inner()->node($path);

        return $node instanceof File ? new TestFile($node) : new TestDirectory($node);
    }

    public function file(string $path): TestFile
    {
        return new TestFile($this->inner()->file($path));
    }

    public function image(string $path): TestImage
    {
        return new TestImage($this->inner()->image($path));
    }

    public function directory(string $path = ''): TestDirectory
    {
        return new TestDirectory($this->inner()->directory($path));
    }

    public function last(): TestFile|TestDirectory
    {
        $node = $this->inner()->last();

        return $node instanceof File ? new TestFile($node) : new TestDirectory($node);
    }

    public function dump(): self
    {
        $this->directory()->recursive()->dump();

        return $this;
    }

    /**
     * @return no-return
     */
    public function dd(): void
    {
        $this->directory()->recursive()->dd();
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }
}
