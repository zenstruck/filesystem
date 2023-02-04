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
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RoutePublicUrlGenerator extends RouteUrlGenerator implements PublicUrlGenerator
{
    public function publicUrl(string $path, Config $config): string
    {
        return $this->generate(
            $path,
            $config->get('parameters', []),
            $config->get('sign'),
            $config->get('expires')
        );
    }
}
