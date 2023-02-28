<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine\Mapping;

use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Filesystem\Node\Path\Namer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Stateless extends Mapping
{
    public function __construct(string $filesystem, string|Namer $namer)
    {
        parent::__construct(self::PATH, $filesystem, $namer);
    }

    public function filesystem(): string
    {
        return parent::filesystem(); // @phpstan-ignore-line
    }

    public function namer(): Namer
    {
        return parent::namer(); // @phpstan-ignore-line
    }
}
