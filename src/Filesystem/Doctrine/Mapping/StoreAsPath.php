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

use Zenstruck\Filesystem\Node\File\Path\Namer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class StoreAsPath extends Stateful
{
    public function __construct(
        string $filesystem,
        string|Namer|null $namer = null,
        array $namerContext = [],
        bool $deleteOnRemove = true,
        bool $deleteOnUpdate = true
    ) {
        parent::__construct($filesystem, $namer, $namerContext, $deleteOnRemove, $deleteOnUpdate);
    }

    public function filesystem(): string
    {
        return parent::filesystem(); // @phpstan-ignore-line
    }
}