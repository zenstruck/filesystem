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
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Psr\Container\ContainerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RouteTemporaryUrlGenerator extends RouteUrlGenerator implements TemporaryUrlGenerator
{
    public function __construct(
        ContainerInterface $container,
        string $route,
        array $routeParameters = [],
    ) {
        parent::__construct($container, $route, $routeParameters, true);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config): string
    {
        return $this->generate(
            path: $path,
            routeParameters: $config->get('parameters', []),
            expires: $expiresAt,
        );
    }
}
