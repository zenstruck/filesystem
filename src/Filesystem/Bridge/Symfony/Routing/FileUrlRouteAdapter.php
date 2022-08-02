<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\Routing;

use League\Flysystem\FilesystemAdapter;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Zenstruck\Filesystem\Adapter\FeatureAwareAdapter;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FileUrlRouteAdapter extends FeatureAwareAdapter implements ServiceSubscriberInterface
{
    protected const FEATURES_ADDED = [FileUrl::class];

    /**
     * @param array{
     *     route: string,
     *     parameters?: array<string,mixed>,
     *     reference_type?: int,
     *     sign?: bool
     * } $config
     */
    public function __construct(FilesystemAdapter $next, private array $config, private ContainerInterface $container)
    {
        parent::__construct($next);
    }

    public function urlFor(File $file, mixed $options = []): Uri
    {
        if (!\is_array($options)) {
            throw new UnsupportedFeature(\sprintf('Can only use array for $options in %s. "%s" given.', self::class, \get_debug_type($options)));
        }

        $url = $this->container->get(UrlGeneratorInterface::class)->generate(
            $this->config['route'] ?? throw new \InvalidArgumentException('A route was not set.'),
            \array_merge($this->config['parameters'] ?? [], $options),
            $this->config['reference_type'] ?? UrlGeneratorInterface::ABSOLUTE_URL
        );

        if ($this->config['sign'] ?? false) {
            $url = $this->container->get(UriSigner::class)->sign($url);
        }

        return Uri::new($url);
    }

    public static function getSubscribedServices(): array
    {
        return [
            UrlGeneratorInterface::class,
            UriSigner::class,
        ];
    }
}
