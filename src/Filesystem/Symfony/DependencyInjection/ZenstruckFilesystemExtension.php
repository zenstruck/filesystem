<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\DependencyInjection;

use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemAdapter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\Flysystem\AdapterFactory;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\LoggableFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Symfony\Form\PendingFileType;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckFilesystemExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->register('.zenstruck_filesystem.filesystem_locator', ServiceLocator::class)
            ->addArgument(new TaggedIteratorArgument('zenstruck_filesystem', 'key'))
            ->addTag('container.service_locator')
        ;

        $container->register('.zenstruck_filesystem.filesystem_registry', FilesystemRegistry::class)
            ->addArgument(new Reference('.zenstruck_filesystem.filesystem_locator'))
            ->addTag('kernel.reset', ['method' => 'reset'])
        ;

        $container->register(MultiFilesystem::class)
            ->addArgument(new Reference('.zenstruck_filesystem.filesystem_locator'))
        ;

        $this->registerFilesystems($mergedConfig, $container);

        // form types
        $container->register('.zenstruck_filesystem.form.pending_file_type', PendingFileType::class)
            ->addTag('form.type')
        ;
    }

    private function registerFilesystems(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!$mergedConfig['filesystems']) {
            return; // no filesystems defined
        }

        $defaultName = $mergedConfig['default_filesystem'] ?? \array_key_first($mergedConfig['filesystems']);

        if (!isset($mergedConfig['filesystems'][$defaultName])) {
            throw new InvalidConfigurationException('Invalid default filesystem name');
        }

        $container->getDefinition(MultiFilesystem::class)
            ->addArgument($defaultName)
        ;

        foreach ($mergedConfig['filesystems'] as $name => $config) {
            $this->registerFilesystem($name, $config, $container, $defaultName);
        }
    }

    private function registerFilesystem(string $name, array $config, ContainerBuilder $container, string $defaultName): void
    {
        if (\str_starts_with($config['dsn'], '@')) {
            $config['dsn'] = new Reference(\mb_substr($config['dsn'], 1));
        } else {
            $container->register($adapterId = '.zenstruck_filesystem.flysystem_adapter.'.$name, FilesystemAdapter::class)
                ->setFactory([AdapterFactory::class, 'createAdapter'])
                ->addArgument($config['dsn'])
            ;
            $config['dsn'] = new Reference($adapterId);
        }

        if ($config['url_prefix']) {
            $config['config']['url_prefix'] = $config['url_prefix'];
        }

        $container->register($flysystemId = '.zenstruck_filesystem.flysystem.'.$name, Flysystem::class)
            ->setArguments([$config['dsn'], $config['config']])
        ;

        $container->register($filesystemId = '.zenstruck_filesystem.filesystem.'.$name, FlysystemFilesystem::class)
            ->setArguments([new Reference($flysystemId), $name])
            ->addTag('zenstruck_filesystem', ['key' => $name])
        ;

        if ($config['log']) {
            $container->register('.zenstruck_filesystem.filesystem.log_'.$name, LoggableFilesystem::class)
                ->setDecoratedService($filesystemId)
                ->setArguments([new Reference('.inner'), new Reference('logger')])
                ->addTag('monolog.logger', ['channel' => 'filesystem'])
            ;
        }

        if ($name === $defaultName) {
            $container->setAlias(Filesystem::class, $filesystemId);
        } else {
            $container->registerAliasForArgument($filesystemId, Filesystem::class, $name.'Filesystem');
        }
    }
}
