<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestImage extends Image
{
    use IsTestFile;

    /**
     * @param int|callable(int):void $expected
     */
    public function assertHeight(int|callable $expected): self
    {
        if (\is_int($expected)) {
            Assert::that($this->height())->is($expected, 'Expected height to be {expected} but is actually {actual}.');

            return $this;
        }

        ($expected)($this->height());

        return $this;
    }

    /**
     * @param int|callable(int):void $expected
     */
    public function assertWidth(int|callable $expected): self
    {
        if (\is_int($expected)) {
            Assert::that($this->width())->is($expected, 'Expected width to be {expected} but is actually {actual}.');

            return $this;
        }

        ($expected)($this->width());

        return $this;
    }
}
