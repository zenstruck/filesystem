<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Glide;

use League\Flysystem\Config;
use League\Glide\Urls\UrlBuilder;
use Zenstruck\Filesystem\Flysystem\TransformUrlGenerator;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
final class GlideTransformUrlGenerator implements TransformUrlGenerator
{
    public function __construct(private UrlBuilder $urlBuilder)
    {
    }

    public function transformUrl(string $path, array|string $filter, Config $config): string
    {
        $filter = match (true) { // @phpstan-ignore-line https://github.com/phpstan/phpstan/issues/8937
            \is_string($filter) => ['p' => $filter], // is glide "preset"
            \is_array($filter) && !\array_is_list($filter) => $filter, // is standard glide parameters
            \is_array($filter) => ['p' => \implode(',', $filter)], // is array of "presets"
        };

        return $this->urlBuilder->getUrl($path, $filter);
    }
}
