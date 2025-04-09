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
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class RouteUrlGenerator
{
    /**
     * @param array<string,mixed> $routeParameters
     */
    public function __construct(
        private ContainerInterface $container,
        private string $route,
        private array $routeParameters = [],
        private bool $signByDefault = false,
        private ?string $defaultExpires = null,
    ) {
    }

    /**
     * @param array<string,mixed> $routeParameters
     */
    final protected function generate(string $path, array $routeParameters, ?bool $sign, string|\DateTimeInterface|null $expires): string
    {
        $routeParameters = \array_merge($this->routeParameters, $routeParameters, ['path' => $path]);
        $sign ??= $this->signByDefault;
        $expires ??= $this->defaultExpires;

        if (null !== $expires) {
            $sign = true;
        }

        $url = $this->container->get(UrlGeneratorInterface::class)
            ->generate($this->route, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL)
        ;

        if (!$sign) {
            return $url;
        }

        if (\is_string($expires)) {
            $expires = new \DateTimeImmutable($expires);
        }

        return $this->container->get(UriSigner::class)->sign($url, $expires);
    }
}
