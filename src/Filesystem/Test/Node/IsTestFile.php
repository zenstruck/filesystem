<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem\Node\File\Checksum;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait IsTestFile
{
    use IsTestNode;

    public function assertContentIs(string $expected): self
    {
        Assert::that($this->contents())->is($expected);

        return $this;
    }

    public function assertContentIsNot(string $expected): self
    {
        Assert::that($this->contents())->isNot($expected);

        return $this;
    }

    public function assertContentContains(string $expected): self
    {
        Assert::that($this->contents())->contains($expected);

        return $this;
    }

    public function assertContentDoesNotContain(string $expected): self
    {
        Assert::that($this->contents())->doesNotContain($expected);

        return $this;
    }

    public function assertMimeTypeIs(string $expected): self
    {
        Assert::that($this->mimeType())->is($expected);

        return $this;
    }

    public function assertMimeTypeIsNot(string $expected): self
    {
        Assert::that($this->mimeType())->isNot($expected);

        return $this;
    }

    /**
     * @param int|callable(Information):void $expected
     */
    public function assertSize(int|callable $expected): self
    {
        if (\is_int($expected)) {
            Assert::that($this->size()->bytes())->is($expected);

            return $this;
        }

        $expected($this->size());

        return $this;
    }

    /**
     * @param string|callable(Checksum):void $expected
     */
    public function assertChecksum(string|callable $expected): self
    {
        if (\is_string($expected)) {
            Assert::that($this->checksum()->toString())->is($expected);

            return $this;
        }

        $expected($this->checksum());

        return $this;
    }
}
