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

use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class RouteUrlGenerator
{
    public function __construct(
        private ContainerInterface $container,
        private string $route,
        private array $routeParameters = [],
        private bool $signByDefault = false,
        private ?string $defaultExpires = null,
    ) {
    }

    final protected function generate(string $path, array $routeParameters, ?bool $sign, string|\DateTimeInterface|null $expires): string
    {
        $routeParameters = \array_merge($this->routeParameters, $routeParameters, ['path' => $path]);
        $sign ??= $this->signByDefault;
        $expires ??= $this->defaultExpires;

        if (null !== $expires) {
            $sign = true;
        }

        if (!$sign) {
            return $this->container->get(UrlGeneratorInterface::class)
                ->generate($this->route, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL)
            ;
        }

        if (!$this->container->has(SignedUrlGenerator::class)) {
            throw new \LogicException('zenstruck/url is required to sign urls. Install with "composer require zenstruck/uri" and be sure the bundle is enabled.');
        }

        $builder = $this->container->get(SignedUrlGenerator::class)->build($this->route, $routeParameters);

        return $expires ? $builder->expires($expires) : $builder;
    }
}
