<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Event;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PreCopyEvent extends PreOperationEvent
{
    public function __construct(Filesystem $filesystem, public string $source, public string $destination, public array $config = [])
    {
        parent::__construct($filesystem);
    }
}
