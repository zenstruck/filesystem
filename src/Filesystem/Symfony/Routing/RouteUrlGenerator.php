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
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class RouteUrlGenerator
{
    private bool $signByDefault;

    /**
     * @param array<string,mixed> $routeParameters
     */
    public function __construct(
        private ContainerInterface $container,
        private string $route,
        private array $routeParameters = [],
        bool $signByDefault = false,
        private ?string $defaultExpires = null,
    ) {
        $this->signByDefault = $this->defaultExpires ? true : $signByDefault;
    }

    /**
     * @param array<string,mixed> $routeParameters
     */
    final protected function generate(string $path, array $routeParameters, string|\DateTimeInterface|null $expires): string
    {
        $expires ??= $this->defaultExpires;
        $url = $this->container->get(UrlGeneratorInterface::class)
            ->generate(
                $this->route,
                \array_merge($this->routeParameters, $routeParameters, ['path' => $path]),
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
        ;

        if ($expires && !$this->signByDefault) {
            throw new \LogicException('Cannot set expiry when signing is disabled.');
        }

        if (!$this->signByDefault) {
            return $url;
        }

        if (null === $expires) {
            @\trigger_error('Generating a signed URL without an expiration is deprecated. Configure a default expiry or pass the "expires" option.', \E_USER_DEPRECATED);
            $constructor = new \ReflectionMethod(UriSigner::class, '__construct');
            $expires = $constructor->getNumberOfParameters() >= 3 ? new \DateTimeImmutable('+1 year') : null;
        }

        if (\is_string($expires)) {
            $expires = new \DateTimeImmutable($expires);
        }

        if ($expires && Kernel::VERSION_ID < 70100) { // @phpstan-ignore smaller.alwaysFalse, booleanAnd.alwaysFalse
            throw new \LogicException('Expiring URLs requires Symfony 7.1 or higher.');
        }

        return $this->container->get(UriSigner::class)->sign($url, $expires);
    }
}
