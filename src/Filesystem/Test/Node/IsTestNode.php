<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait IsTestNode
{
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

    public function ensureImage(array $config = []): TestImage
    {
        if ($this instanceof TestImage) {
            return $this;
        }

        return new TestImage($this->inner()->ensureImage($config));
    }

    public function assertVisibilityIs(string $expected): self
    {
        Assert::that($this->visibility())->is($expected, 'Expected visibility to be {expected} but is actually {actual}.');

        return $this;
    }

    /**
     * @param string|\DateTimeInterface|callable(\DateTimeInterface):void $expected
     */
    public function assertLastModified(string|\DateTimeInterface|callable $expected): self
    {
        $actual = $this->lastModified();

        if ($expected instanceof \DateTimeInterface) {
            $expected = $expected->getTimestamp();
        }

        if (\is_string($expected)) {
            Assert::that($actual->getTimestamp())->is($expected);

            return $this;
        }

        $expected($actual); // @phpstan-ignore-line

        return $this;
    }

    abstract protected function inner(): Node;
}
