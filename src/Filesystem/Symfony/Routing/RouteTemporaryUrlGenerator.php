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
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class RouteTemporaryUrlGenerator implements TemporaryUrlGenerator
{
    public function __construct(
        private SignedUrlGenerator $router,
        private string $route,
        private array $routeParameters = [],
    ) {
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config): string
    {
        return $this->router->temporary(
            $expiresAt,
            $this->route,
            \array_merge($this->routeParameters, $config->get('parameters', []), ['path' => $path])
        );
    }
}
