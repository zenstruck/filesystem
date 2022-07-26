<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait IsTestNode
{
    /**
     * @param File|Directory<Node> $node
     */
    public function __construct(File|Directory $node)
    {
        $this->operator = $node->operator; // @phpstan-ignore-line
        $this->path = $node->path; // @phpstan-ignore-line
    }

    /**
     * @return $this
     */
    public function assertVisibilityIs(string $expected): self
    {
        Assert::that($this->visibility())->is($expected, 'Expected visibility to be {expected} but is actually {actual}.');

        return $this;
    }

    /**
     * @param string|\DateTimeInterface|callable(\DateTimeInterface):void $expected
     *
     * @return $this
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
}
