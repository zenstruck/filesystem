<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\WrappedDirectory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements Directory<Node>
 */
final class TestDirectory implements Directory
{
    use IsTestNode, WrappedDirectory {
        IsTestNode::ensureFile insteadof WrappedDirectory;
        IsTestNode::ensureImage insteadof WrappedDirectory;
        IsTestNode::ensureDirectory insteadof WrappedDirectory;
    }

    /**
     * @param Directory<Node> $directory
     */
    public function __construct(Directory $directory)
    {
        $this->inner = $directory;
    }

    public function assertCount(int $expected): self
    {
        Assert::that($this)->hasCount($expected, 'Expected Directory to contain {expected} nodes but contains {actual}.');

        return $this;
    }

    public function dump(): self
    {
        $files = \array_map(static fn($d) => (string) $d, \iterator_to_array($this));

        \function_exists('dump') ? dump($files) : \var_dump($files);

        return $this;
    }

    /**
     * @return no-return
     */
    public function dd(): void
    {
        $this->dump();
        exit(1);
    }
}
