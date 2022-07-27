<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\DependencyInjection;

use League\Flysystem\FilesystemAdapter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\AdapterFactory;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\LoggableFilesystem;
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

        $container->register('.zenstruck_filesystem.adapter_factory', AdapterFactory::class);

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
        if (\str_starts_with($config['dsn'], '@')) {
            $config['dsn'] = new Reference(\mb_substr($config['dsn'], 1));
        } else {
            $container->register($adapterId = '.zenstruck_filesystem.adapter.'.$name, FilesystemAdapter::class)
                ->setFactory([new Reference('.zenstruck_filesystem.adapter_factory'), 'create'])
                ->setArguments([$config['dsn'], $name])
            ;
            $config['dsn'] = new Reference($adapterId);
        }

        if ($config['url_prefix']) {
            $config['config']['url_prefixes'] = (array) $config['url_prefix'];
        }

        $filesystemDef = $container->register($filesystem = 'zenstruck_filesystem.filesystem.'.$name, AdapterFilesystem::class)
            ->setArguments([$config['dsn'], $config['config'], $name])
            ->addTag('zenstruck_filesystem', ['key' => $name])
        ;

        if ($config['readonly']) {
            $container->register('.zenstruck_filesystem.filesystem.readonly_'.$name, ReadonlyFilesystem::class)
                ->setDecoratedService($filesystem)
                ->setArguments([new Reference('.inner')])
            ;
        }

        if ($config['test']) {
            if (\str_starts_with($config['test'], '@')) {
                $config['test'] = new Reference(\mb_substr($config['test'], 1));
            } else {
                $container->register($adapterId = '.zenstruck_filesystem.test_adapter.'.$name, FilesystemAdapter::class)
                    ->setFactory([new Reference('.zenstruck_filesystem.adapter_factory'), 'create'])
                    ->setArguments([$config['test'], $name])
                ;
                $config['test'] = new Reference($adapterId);
            }

            $filesystemDef->addMethodCall('swap', [new Reference($config['test'])]);
        }

        if ($config['log']) {
            $container->register('.zenstruck_filesystem.filesystem.log_'.$name, LoggableFilesystem::class)
                ->setDecoratedService($filesystem)
                ->setArguments([new Reference('.inner'), new Reference('logger')])
                ->addTag('monolog.logger', ['channel' => 'filesystem'])
            ;
        }

        // todo traceable filesystem if debug

        $container->registerAliasForArgument($filesystem, Filesystem::class, $name);
    }
}
