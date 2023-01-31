<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Namer
{
    public function __construct(private string $id, private array $context = [])
    {
    }

    final public function id(): string
    {
        return $this->id;
    }

    final public function context(): array
    {
        return $this->context;
    }

    final public function with(array $context): static
    {
        $clone = clone $this;
        $clone->context = \array_merge($clone->context, $context);

        return $clone;
    }
}
