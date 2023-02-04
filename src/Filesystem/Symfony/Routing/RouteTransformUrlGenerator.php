<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\Routing;

use League\Flysystem\Config;
use Zenstruck\Filesystem\Feature\TransformUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RouteTransformUrlGenerator extends RouteUrlGenerator implements TransformUrlGenerator
{
    public function transformUrl(string $path, array|string $filter, Config $config): string
    {
        if (\is_string($filter)) {
            $filter = ['filter' => $filter];
        }

        return $this->generate(
            $path,
            \array_merge($config->get('parameters', []), $filter),
            $config->get('sign'),
            $config->get('expires')
        );
    }
}
