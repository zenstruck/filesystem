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
 */
final class RouteTemporaryUrlGenerator extends RouteUrlGenerator implements TemporaryUrlGenerator
{
    public function __construct(
        private SignedUrlGenerator $router,
        private string $route,
        private array $routeParameters = [],
    ) {
        parent::__construct($this->router, $this->route, $this->routeParameters);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, Config $config): string
    {
        return $this->generate($config->get('parameters', []), sign: true, expires: $expiresAt);
    }
}
