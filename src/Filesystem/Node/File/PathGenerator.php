<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File;

use Psr\Container\ContainerInterface;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\Path\CallbackPathGenerator;
use Zenstruck\Filesystem\Node\File\Path\ExpressionPathGenerator;
use Zenstruck\Filesystem\Node\File\Path\Generator;
use Zenstruck\Filesystem\Node\File\Path\Namer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PathGenerator
{
    /** @var array<string,Generator> */
    private static array $defaultGenerators = [];

    /**
     * @param array<string,Generator>|ContainerInterface $generators
     */
    public function __construct(private array|ContainerInterface $generators = [])
    {
    }

    public function generate(string|Namer|callable $namer, Node $node, array $context = []): string
    {
        if (\is_string($namer)) {
            $namer = new Namer($namer);
        }

        if (!$namer instanceof Namer && \is_callable($namer)) {
            return (new CallbackPathGenerator($namer))->generatePath($node, $context);
        }

        return $this->get($namer->id())->generatePath($node, $namer->with($context)->context());
    }

    private function get(string $id): Generator
    {
        if (\is_array($this->generators) && isset($this->generators[$id])) {
            return $this->generators[$id];
        }

        if ($this->generators instanceof ContainerInterface && $this->generators->has($id)) {
            return $this->generators->get($id);
        }

        return self::defaultGenerator($id);
    }

    private static function defaultGenerator(string $id): Generator
    {
        return self::$defaultGenerators[$id] ??= match ($id) {
            'expression' => new ExpressionPathGenerator(),
            default => throw new \InvalidArgumentException(\sprintf('No path generator available for namer "%s".', $id)),
        };
    }
}
