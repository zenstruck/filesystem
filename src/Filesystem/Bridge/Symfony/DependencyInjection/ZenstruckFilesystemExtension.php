<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\StaticInMemoryAdapter;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\ReadonlyFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFilesystemExtension extends ConfigurableExtension
{
    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($container->getParameter('kernel.environment'));
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!$mergedConfig['filesystems']) {
            return; // no filesystems registered
        }

        foreach ($mergedConfig['filesystems'] as $name => $config) {
            $this->addFilesystem($name, $config, $container);
        }

        $defaultName = $mergedConfig['default_filesystem'] ?? \array_key_first($mergedConfig['filesystems']);

        if (!isset($mergedConfig['filesystems'][$defaultName])) {
            throw new InvalidConfigurationException('Invalid default filesystem name');
        }

        $container->register(MultiFilesystem::class)
            ->setArguments([
                new ServiceLocatorArgument(new TaggedIteratorArgument('zenstruck_filesystem', 'key')),
                $defaultName,
            ])
        ;

        $container->setAlias(Filesystem::class, MultiFilesystem::class);
    }

    private function addFilesystem(string $name, array $config, ContainerBuilder $container): void
    {
        $filesystemDef = $container->register($filesystem = 'zenstruck_filesystem.adapter_'.$name, AdapterFilesystem::class)
            ->setArguments([$config['dsn'], $config['config'], $name])
            ->addTag('zenstruck_filesystem', ['key' => $name])
        ;

        if ($config['readonly']) {
            $container->register('zenstruck_filesystem.readonly_'.$name, ReadonlyFilesystem::class)
                ->setDecoratedService($filesystem)
                ->setArguments([new Reference('.inner')])
            ;
        }

        if ($config['test']) {
            StaticInMemoryAdapter::ensureSupported();

            $container->register($testName = 'zenstruck_filesystem.test_'.$name, StaticInMemoryAdapter::class)
                ->setArguments([$name])
            ;

            $filesystemDef->addMethodCall('swap', [new Reference($testName)]);
        }

        if ($config['log']) {
            // todo
        }

        if ($config['trace']) {
            // todo
        }

        $container->registerAliasForArgument($filesystem, Filesystem::class, $name);
    }
}
