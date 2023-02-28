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
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Doctrine\EventListener\NodeLifecycleListener;
use Zenstruck\Filesystem\Doctrine\EventListener\NodeMappingListener;
use Zenstruck\Filesystem\Doctrine\FileMappingLoader;
use Zenstruck\Filesystem\Doctrine\Twig\FileMappingLoaderExtension;
use Zenstruck\Filesystem\Event\EventDispatcherFilesystem;
use Zenstruck\Filesystem\Flysystem\AdapterFactory;
use Zenstruck\Filesystem\Flysystem\TransformUrlGenerator;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\LoggableFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node\Path\Generator;
use Zenstruck\Filesystem\Node\Path\Generator\ExpressionPathGenerator;
use Zenstruck\Filesystem\Node\PathGenerator;
use Zenstruck\Filesystem\ScopedFilesystem;
use Zenstruck\Filesystem\Symfony\Command\FilesystemPurgeCommand;
use Zenstruck\Filesystem\Symfony\Form\PendingFileType;
use Zenstruck\Filesystem\Symfony\HttpKernel\FilesystemDataCollector;
use Zenstruck\Filesystem\Symfony\Routing\RoutePublicUrlGenerator;
use Zenstruck\Filesystem\Symfony\Routing\RouteTemporaryUrlGenerator;
use Zenstruck\Filesystem\Symfony\Routing\RouteTransformUrlGenerator;
use Zenstruck\Filesystem\Symfony\Serializer\NodeNormalizer;
use Zenstruck\Filesystem\TraceableFilesystem;
use Zenstruck\Filesystem\Twig\TwigPathGenerator;
use Zenstruck\Uri\Bridge\Symfony\Routing\SignedUrlGenerator;
use Zenstruck\Uri\Bridge\Symfony\ZenstruckUriBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ZenstruckFilesystemExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $locator = $container->register('zenstruck_filesystem.filesystem_locator', ServiceLocator::class)
            ->addArgument(new TaggedIteratorArgument('zenstruck_filesystem', 'key'))
            ->addTag('container.service_locator')
        ;

        $multi = $container->register(MultiFilesystem::class)
            ->addArgument(new Reference('zenstruck_filesystem.filesystem_locator'))
        ;

        if ('test' === $container->getParameter('kernel.environment')) {
            $locator->setPublic(true);
            $multi->setPublic(true);
        }

        if ($container->getParameter('kernel.debug')) {
            $container->register('.zenstruck_filesystem.data_collector', FilesystemDataCollector::class)
                ->addTag('data_collector', [
                    'template' => '@ZenstruckFilesystem/Collector/filesystem.html.twig',
                    'id' => 'filesystem',
                ])
            ;
        }

        // form types
        $container->register('.zenstruck_filesystem.form.pending_file_type', PendingFileType::class)
            ->addTag('form.type')
        ;

        // normalizer
        $container->register('.zenstruck_filesystem.node_normalizer', NodeNormalizer::class)
            ->addArgument(new ServiceLocatorArgument([
                PathGenerator::class => new Reference(PathGenerator::class),
                'filesystem_locator' => new Reference('zenstruck_filesystem.filesystem_locator'),
            ]))
            ->addTag('serializer.normalizer')
        ;

        // commands
        $container->register('.zenstruck_filesystem.purge_command', FilesystemPurgeCommand::class)
            ->addArgument(new Reference('zenstruck_filesystem.filesystem_locator'))
            ->addTag('console.command')
        ;

        $this->registerFilesystems($mergedConfig, $container);
        $this->registerPathGenerators($container);

        if ($mergedConfig['doctrine']['enabled']) {
            $this->registerDoctrine($container, $mergedConfig['doctrine']);
        }
    }

    private function registerDoctrine(ContainerBuilder $container, array $config): void
    {
        $container->register('.zenstruck_filesystem.doctrine.mapping_listener', NodeMappingListener::class)
            ->addTag('doctrine.event_listener', ['event' => 'loadClassMetadata'])
        ;

        $container->register(FileMappingLoader::class)
            ->setArguments([
                new Reference('doctrine'),
                new Reference('.zenstruck_filesystem.doctrine.lifecycle_listener'),
            ])
            ->addTag('twig.runtime')
        ;

        $listener = $container->register('.zenstruck_filesystem.doctrine.lifecycle_listener', NodeLifecycleListener::class)
            ->addArgument(new ServiceLocatorArgument([
                PathGenerator::class => new Reference(PathGenerator::class),
                'filesystem_locator' => new Reference('zenstruck_filesystem.filesystem_locator'),
            ]))
            ->addTag('doctrine.event_listener', ['event' => 'preUpdate'])
            ->addTag('doctrine.event_listener', ['event' => 'postFlush'])
            ->addTag('doctrine.event_listener', ['event' => 'prePersist'])
            ->addTag('doctrine.event_listener', ['event' => 'onClear'])
        ;

        if ($config['lifecycle']['autoload']) {
            $listener->addTag('doctrine.event_listener', ['event' => 'postLoad']);
        }

        if ($config['lifecycle']['delete_on_remove']) {
            $listener->addTag('doctrine.event_listener', ['event' => 'postRemove']);
        }

        if (isset($container->getParameter('kernel.bundles')['TwigBundle'])) {
            $container->register('.zenstruck_filesystem.doctrine.twig_extension', FileMappingLoaderExtension::class)
                ->addTag('twig.extension')
            ;
        }
    }

    private function registerPathGenerators(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(Generator::class)
            ->addTag('zenstruck_filesystem.path_generator')
        ;

        $container->register(PathGenerator::class)
            ->addArgument(new ServiceLocatorArgument(new TaggedIteratorArgument('zenstruck_filesystem.path_generator', 'key', needsIndexes: true)))
        ;

        $expression = $container->register('.zenstruck_filesystem.path_generator.expression', ExpressionPathGenerator::class)
            ->addTag('zenstruck_filesystem.path_generator', ['key' => 'expression'])
        ;

        if (
            ContainerBuilder::willBeAvailable('symfony/string', SluggerInterface::class, ['symfony/framework-bundle']) &&
            ContainerBuilder::willBeAvailable('symfony/translation', LocaleAwareInterface::class, ['symfony/framework-bundle'])
        ) {
            $expression->addArgument(new Reference('slugger'));
        }

        if (isset($container->getParameter('kernel.bundles')['TwigBundle'])) {
            $container->register('.zenstruck_filesystem.path_generator.twig', TwigPathGenerator::class)
                ->addArgument(new Reference('twig'))
                ->addTag('zenstruck_filesystem.path_generator', ['key' => 'twig'])
            ;
        }
    }

    private function registerFilesystems(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!$mergedConfig['filesystems']) {
            return; // no filesystems defined
        }

        $defaultName = $mergedConfig['default_filesystem'];

        if ($defaultName) {
            $container->getDefinition(MultiFilesystem::class)
                ->addArgument($defaultName)
            ;
        }

        foreach ($mergedConfig['filesystems'] as $name => $config) {
            $this->registerFilesystem($name, $config, $container, $defaultName, \array_keys($mergedConfig['filesystems']));
        }
    }

    private function registerFilesystem(string $name, array $config, ContainerBuilder $container, ?string $defaultName, array $filesystemNames): void
    {
        if ('static-in-memory' === $config['dsn']) {
            $config['dsn'] = "in-memory:{$name}";
        }

        if ($config['reset_before_tests']) {
            if (!$container->hasParameter('zenstruck_filesystem.reset_before_tests_filesystems')) {
                $container->setParameter('zenstruck_filesystem.reset_before_tests_filesystems', []);
            }

            $container->setParameter(
                'zenstruck_filesystem.reset_before_tests_filesystems',
                \array_merge($container->getParameter('zenstruck_filesystem.reset_before_tests_filesystems'), [$name]) // @phpstan-ignore-line
            );
        }

        if (\str_starts_with($config['dsn'], 'scoped:') && 2 === \count($parts = \explode(':', \mb_substr($config['dsn'], 7), 2))) {
            [$scopedName, $scopedPath] = $parts;

            if (!\in_array($scopedName, $filesystemNames, true)) {
                throw new InvalidConfigurationException(\sprintf('"%s" is not a registered filesystem.', $scopedName));
            }

            $container->register($filesystemId = 'zenstruck_filesystem.filesystem.'.$name, ScopedFilesystem::class)
                ->setArguments([
                    new Reference('zenstruck_filesystem.filesystem.'.$scopedName),
                    $scopedPath,
                    $name,
                ])
                ->addTag('zenstruck_filesystem', ['key' => $name])
            ;

            $container->registerAliasForArgument($filesystemId, Filesystem::class, $name.'Filesystem');

            if ($name === $defaultName) {
                $container->setAlias(Filesystem::class, $filesystemId);
            }

            return;
        }

        if (\str_starts_with($config['dsn'], '@')) {
            $config['dsn'] = new Reference(\mb_substr($config['dsn'], 1));
        } else {
            $container->register($adapterId = '.zenstruck_filesystem.flysystem_adapter.'.$name, FilesystemAdapter::class)
                ->setFactory([AdapterFactory::class, 'createAdapter'])
                ->addArgument($config['dsn'])
            ;
            $config['dsn'] = new Reference($adapterId);
        }

        $features = [];
        $canSignUrls = \in_array(ZenstruckUriBundle::class, (array) $container->getParameter('kernel.bundles'), true);
        $routers = [UrlGeneratorInterface::class => new Reference('router')];

        if ($canSignUrls) {
            $routers[SignedUrlGenerator::class] = new Reference(SignedUrlGenerator::class);
        }

        $routers = new ServiceLocatorArgument($routers);

        // public url config
        switch (true) {
            case isset($config['public_url']['prefix']):
                $config['config']['public_url'] = $config['public_url']['prefix'];

                break;

            case isset($config['public_url']['service']):
                $features[PublicUrlGenerator::class] = new Reference($config['public_url']['service']);

                break;

            case isset($config['public_url']['route']):
                $container->register($id = '.zenstruck_filesystem.filesystem_public_url.'.$name, RoutePublicUrlGenerator::class)
                    ->setArguments([
                        $routers,
                        $config['public_url']['route']['name'],
                        $config['public_url']['route']['parameters'],
                        $config['public_url']['route']['sign'],
                        $config['public_url']['route']['expires'],
                    ])
                ;

                $features[PublicUrlGenerator::class] = new Reference($id);

                if ($canSignUrls) {
                    $container->register($id = '.zenstruck_filesystem.filesystem_temporary_url.'.$name, RouteTemporaryUrlGenerator::class)
                        ->setArguments([
                            new Reference(SignedUrlGenerator::class),
                            $config['public_url']['route']['name'],
                            $config['public_url']['route']['parameters'],
                        ])
                    ;

                    $features[TemporaryUrlGenerator::class] = new Reference($id);
                }

                break;
        }

        // temporary url config
        switch (true) {
            case isset($config['temporary_url']['service']):
                $features[TemporaryUrlGenerator::class] = new Reference($config['temporary_url']['service']);

                break;

            case isset($config['temporary_url']['route']):
                if (!$canSignUrls) {
                    throw new LogicException('zenstruck/url is required to sign urls. Install with "composer require zenstruck/uri" and be sure the bundle is enabled.');
                }

                $container->register($id = '.zenstruck_filesystem.filesystem_temporary_url.'.$name, RouteTemporaryUrlGenerator::class)
                    ->setArguments([
                        new Reference(SignedUrlGenerator::class),
                        $config['temporary_url']['route']['name'],
                        $config['temporary_url']['route']['parameters'],
                    ])
                ;

                $features[TemporaryUrlGenerator::class] = new Reference($id);

                break;
        }

        // image transform url config
        switch (true) {
            case isset($config['image_url']['service']):
                $features[TransformUrlGenerator::class] = new Reference($config['image_url']['service']);

                break;

            case isset($config['image_url']['route']):
                $container->register($id = '.zenstruck_filesystem.filesystem_image_url.'.$name, RouteTransformUrlGenerator::class)
                    ->setArguments([
                        $routers,
                        $config['image_url']['route']['name'],
                        $config['image_url']['route']['parameters'],
                        $config['image_url']['route']['sign'],
                        $config['image_url']['route']['expires'],
                    ])
                ;

                $features[TransformUrlGenerator::class] = new Reference($id);

                break;
        }

        $container->register($flysystemId = 'zenstruck_filesystem.flysystem.'.$name, Flysystem::class)
            ->setArguments([$config['dsn'], $config['config']])
        ;

        $filesystemDef = $container->register($filesystemId = 'zenstruck_filesystem.filesystem.'.$name, FlysystemFilesystem::class)
            ->setArguments([new Reference($flysystemId), $name, new ServiceLocatorArgument($features)])
            ->addTag('zenstruck_filesystem', ['key' => $name])
        ;

        if ($config['lazy']) {
            $filesystemDef
                ->setLazy(true)
                ->addTag('proxy', ['interface' => Filesystem::class])
            ;
        }

        if ($config['log']['enabled']) {
            $container->register('.zenstruck_filesystem.filesystem.log_'.$name, LoggableFilesystem::class)
                ->setDecoratedService($filesystemId)
                ->setArguments([new Reference('.inner'), new Reference('logger'), $config['log']])
                ->addTag('monolog.logger', ['channel' => 'filesystem'])
            ;
        }

        if ($config['events']['enabled']) {
            $container->register('.zenstruck_filesystem.filesystem.events_'.$name, EventDispatcherFilesystem::class)
                ->setDecoratedService($filesystemId)
                ->setArguments([new Reference('.inner'), new Reference('event_dispatcher'), $config['events']])
            ;
        }

        if ($container->getParameter('kernel.debug')) {
            $container->register('.zenstruck_filesystem.filesystem.traceable_'.$name, TraceableFilesystem::class)
                ->setDecoratedService($filesystemId)
                ->setArguments([new Reference('.inner'), new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
                ->addTag('kernel.reset', ['method' => 'reset'])
            ;

            $container->getDefinition('.zenstruck_filesystem.data_collector')
                ->addMethodCall('addFilesystem', [new Reference('.zenstruck_filesystem.filesystem.traceable_'.$name)])
            ;
        }

        $container->registerAliasForArgument($filesystemId, Filesystem::class, $name.'Filesystem');

        if ($name === $defaultName) {
            $container->setAlias(Filesystem::class, $filesystemId);
        }
    }
}
