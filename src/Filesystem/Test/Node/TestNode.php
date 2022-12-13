<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\DecoratedNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class TestNode implements Node
{
    use DecoratedNode;

    public function ensureDirectory(): TestDirectory
    {
        if ($this instanceof TestDirectory) {
            return $this;
        }

        return new TestDirectory($this->inner()->ensureDirectory());
    }

    public function ensureFile(): TestFile
    {
        if ($this instanceof TestFile) {
            return $this;
        }

        return new TestFile($this->inner()->ensureFile());
    }

    public function ensureImage(): TestImage
    {
        if ($this instanceof TestImage) {
            return $this;
        }

        return new TestImage($this->inner()->ensureImage());
    }

    public function assertVisibilityIs(string $expected): static
    {
        Assert::that($this->visibility())->is($expected, 'Expected visibility to be {expected} but is actually {actual}.');

        return $this;
    }

    /**
     * @param string|\DateTimeInterface|callable(\DateTimeInterface):void $expected
     */
    public function assertLastModified(string|\DateTimeInterface|callable $expected): static
    {
        $actual = $this->lastModified();

        if ($expected instanceof \DateTimeInterface) {
            $expected = $expected->getTimestamp();
        }

        if (!\is_callable($expected)) {
            Assert::that($actual->getTimestamp())->is($expected);

            return $this;
        }

        $expected($actual);

        return $this;
    }
}
