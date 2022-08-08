<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\Routing;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\SignedUrl\Generator;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class RouteFileUrlFeature implements FileUrl, ServiceSubscriberInterface
{
    /**
     * @param array{
     *     name: string,
     *     parameters?: array<string,mixed>,
     *     reference_type?: int,
     *     sign?: bool,
     *     expires?: int|string,
     * } $config
     */
    public function __construct(private array $config, private ContainerInterface $container)
    {
    }

    public function urlFor(File $file, mixed $options = []): Uri
    {
        if (!\is_array($options)) {
            throw new UnsupportedFeature(\sprintf('Can only use array for $options in %s. "%s" given.', self::class, \get_debug_type($options)));
        }

        $sign = $this->config['sign'] ?? false;
        $expires = $options['expires'] ?? $this->config['expires'] ?? null;

        unset($options['expires']);

        if ($expires) {
            $sign = true;
        }

        $name = $this->config['name'] ?? throw new \InvalidArgumentException('A route was not set.');
        $parameters = \array_merge($this->config['parameters'] ?? [], $options, ['path' => $file->path()]);
        $ref = $this->config['reference_type'] ?? UrlGeneratorInterface::ABSOLUTE_URL;

        if ($expires) {
            if (!$this->container->has(Generator::class)) {
                throw new \LogicException('zenstruck/signed-url-bundle is required to create expiring URLs (composer require zenstruck/signed-url-bundle)');
            }

            return Uri::new($this->container->get(Generator::class)->build($name, $parameters, $ref)->expires($expires));
        }

        $url = $this->container->get(UrlGeneratorInterface::class)->generate($name, $parameters, $ref);

        if ($sign) {
            $url = $this->container->get(UriSigner::class)->sign($url);
        }

        return Uri::new($url);
    }

    public static function getSubscribedServices(): array
    {
        return [
            UrlGeneratorInterface::class,
            UriSigner::class,
            '?'.Generator::class,
        ];
    }
}
