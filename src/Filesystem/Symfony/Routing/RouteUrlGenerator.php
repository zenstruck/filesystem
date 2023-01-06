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

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class RouteUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private string $route,
        private array $routeParameters = [],
        private bool $signByDefault = false,
        private ?string $defaultExpires = null,
    ) {
    }

    final protected function generate(array $routeParameters, ?bool $sign, string|\DateTimeInterface|null $expires): string
    {
        $routeParameters = \array_merge($this->routeParameters, $routeParameters);
        $sign ??= $this->signByDefault;
        $expires ??= $this->defaultExpires;

        if (null !== $expires) {
            $sign = true;
        }

        if (!$sign) {
            return $this->router->generate($this->route, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if (!$this->router instanceof SignedUrlGenerator) {
            throw new \LogicException('zenstruck/url is required to sign urls. Install with "composer require zenstruck/uri" and be sure the bundle is enabled.');
        }

        $builder = $this->router->build($this->route, $routeParameters);

        return $expires ? $builder->expires($expires) : $builder;
    }
}
