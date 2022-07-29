<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of Node
 *
 * @extends Directory<T>
 */
final class TestDirectory extends Directory
{
    use IsTestNode;

    /**
     * @return self<T>
     */
    public function assertCount(int $expected): self
    {
        Assert::that($this)->hasCount($expected, 'Expected Directory to contain {expected} nodes but contains {actual}.');

        return $this;
    }

    /**
     * @return self<T>
     */
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
