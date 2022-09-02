<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\ArchiveFile;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\WrappedNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements Directory<Node>
 */
final class TestDirectory implements Directory
{
    use IsTestNode, WrappedNode {
        IsTestNode::ensureFile insteadof WrappedNode;
        IsTestNode::ensureImage insteadof WrappedNode;
        IsTestNode::ensureDirectory insteadof WrappedNode;
    }

    /**
     * @param Directory<Node> $directory
     */
    public function __construct(private Directory $directory)
    {
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

    public function recursive(): self
    {
        return new self($this->inner()->recursive());
    }

    public function filter(callable $predicate): self
    {
        return new self($this->inner()->filter($predicate));
    }

    public function largerThan(string|int $size): self // @phpstan-ignore-line
    {
        return new self($this->inner()->largerThan($size)); // @phpstan-ignore-line
    }

    public function smallerThan(string|int $size): self // @phpstan-ignore-line
    {
        return new self($this->inner()->smallerThan($size)); // @phpstan-ignore-line
    }

    public function sizeWithin(string|int $min, string|int $max): self // @phpstan-ignore-line
    {
        return new self($this->inner()->sizeWithin($min, $max)); // @phpstan-ignore-line
    }

    public function olderThan(\DateTimeInterface|int|string $date): self
    {
        return new self($this->inner()->olderThan($date));
    }

    public function newerThan(\DateTimeInterface|int|string $date): self
    {
        return new self($this->inner()->newerThan($date));
    }

    public function modifiedBetween(\DateTimeInterface|int|string $min, \DateTimeInterface|int|string $max): self
    {
        return new self($this->inner()->modifiedBetween($min, $max));
    }

    public function matchingName(string|array $pattern): self
    {
        return new self($this->inner()->matchingName($pattern));
    }

    public function notMatchingName(string|array $pattern): self
    {
        return new self($this->inner()->notMatchingName($pattern));
    }

    public function matchingPath(string|array $pattern): self
    {
        return new self($this->inner()->matchingPath($pattern));
    }

    public function notMatchingPath(string|array $pattern): self
    {
        return new self($this->inner()->notMatchingPath($pattern));
    }

    public function files(): self // @phpstan-ignore-line
    {
        return new self($this->inner()->files()); // @phpstan-ignore-line
    }

    public function directories(): self // @phpstan-ignore-line
    {
        return new self($this->inner()->directories()); // @phpstan-ignore-line
    }

    public function zip(?string $filename = null, array $config = []): ArchiveFile
    {
        return $this->inner()->zip($filename, $config);
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->inner()->getIterator() as $node) {
            yield $node instanceof File ? new TestFile($node) : new self($node); // @phpstan-ignore-line
        }
    }

    /**
     * @return Directory<Node>
     */
    protected function inner(): Directory
    {
        return $this->directory;
    }
}
