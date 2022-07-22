<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ChainNodeConfigProvider implements NodeConfigProvider
{
    /**
     * @param NodeConfigProvider[] $providers
     */
    public function __construct(private iterable $providers)
    {
    }

    public function configFor(string $class): array
    {
        foreach ($this->providers as $provider) {
            if ($config = $provider->configFor($class)) {
                return $config;
            }
        }

        return [];
    }

    public function managedClasses(): iterable
    {
        foreach ($this->providers as $provider) {
            yield from $provider->managedClasses();
        }
    }
}
