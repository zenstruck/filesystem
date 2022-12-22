<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine\Attribute;

use Zenstruck\Filesystem\Node\File\LazyFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class HasFiles
{
    /**
     * @internal
     *
     * @var array<string,Mapping>
     */
    public array $mappings = [];

    /**
     * @internal
     *
     * @var array<string,array{0:class-string<LazyFile>,1:Mapping}>
     */
    public array $virtualMappings = [];

    public function __construct(
        /**
         * Whether to autoload mapped files.
         *
         * @readonly
         */
        public bool $autoload = true,
    ) {
    }
}
