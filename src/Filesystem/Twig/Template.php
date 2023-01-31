<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Twig;

use Zenstruck\Filesystem\Node\Path\Namer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Template extends Namer implements \Stringable
{
    public function __construct(private string $value, array $context = [])
    {
        $context['template'] = $this;

        parent::__construct('twig', $context);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
