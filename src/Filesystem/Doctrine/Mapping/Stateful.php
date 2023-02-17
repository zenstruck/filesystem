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
abstract class Stateful extends Mapping
{
    public function __construct(
        string|array $metadata,
        ?string $filesystem = null,
        string|Namer|null $namer = null,
        public bool $deleteOnRemove = true,
        public bool $deleteOnUpdate = true,
        public array $column = [],
    ) {
        parent::__construct($metadata, $filesystem, $namer);
    }
}
