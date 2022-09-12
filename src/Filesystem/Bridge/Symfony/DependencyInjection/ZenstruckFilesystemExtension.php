<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\DependencyInjection;

use League\Flysystem\FilesystemAdapter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Twig\Environment;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\AdapterFactory;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Bridge\Doctrine\ObjectNodeLoader;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\CacheNodeConfigProvider;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\EventListener\NodeLifecycleSubscriber;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\ChecksumNamer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\ExpressionLanguageNamer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\ExpressionNamer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\SlugifyNamer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\TwigNamer;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\ORMNodeConfigProvider;
use Zenstruck\Filesystem\Bridge\Symfony\HttpKernel\FilesystemDataCollector;
use Zenstruck\Filesystem\Bridge\Symfony\Routing\RouteFileUrlFeature;
use Zenstruck\Filesystem\Bridge\Symfony\Serializer\NodeNormalizer;
use Zenstruck\Filesystem\Bridge\Twig\ObjectNodeLoaderExtension;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Feature\FileUrl\PrefixFileUrlFeature;
use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\LoggableFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\ReadonlyFilesystem;
use Zenstruck\Filesystem\TraceableFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ZenstruckFilesystemExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!$mergedConfig['filesystems']) {
            return; // no filesystems registered
        }

        $container->register('.zenstruck_filesystem.adapter_factory', AdapterFactory::class);

        $container->register('.zenstruck_filesystem.filesystem_locator', ServiceLocator::class)
            ->addArgument(new TaggedIteratorArgument('zenstruck_filesystem', 'key'))
            ->addTag('container.service_locator')
        ;

        $container->register('.zenstruck_filesystem.filesystem_registry', FilesystemRegistry::class)
            ->addArgument(new Reference('.zenstruck_filesystem.filesystem_locator'))
            ->addTag('kernel.reset', ['method' => 'reset'])
        ;

        $container->register('.zenstruck_filesystem.node_normalizer', NodeNormalizer::class)
            ->addArgument(new ServiceLocatorArgument([
                FilesystemRegistry::class => new Reference('.zenstruck_filesystem.filesystem_registry'),
            ]))
            ->addTag('serializer.normalizer')
        ;

        if ($container->getParameter('kernel.debug')) {
            $container->register('.zenstruck_filesystem.data_collector', FilesystemDataCollector::class)
                ->addTag('data_collector', [
                    'template' => '@ZenstruckFilesystem/Collector/filesystem.html.twig',
                    'id' => 'filesystem',
                ])
            ;
        }

        $defaultName = $mergedConfig['default_filesystem'] ?? \array_key_first($mergedConfig['filesystems']);

        if (!isset($mergedConfig['filesystems'][$defaultName])) {
            throw new InvalidConfigurationException('Invalid default filesystem name');
        }

        foreach ($mergedConfig['filesystems'] as $name => $config) {
            $this->addFilesystem($name, $config, $container, $defaultName);
        }

        $container->register(MultiFilesystem::class)
            ->setArguments([
                new Reference('.zenstruck_filesystem.filesystem_locator'),
                $defaultName,
            ])
        ;

        if ($mergedConfig['doctrine']['enabled']) {
            $this->addDoctrineConfig($mergedConfig['doctrine'], $container);
        }
    }

    private function addDoctrineConfig(array $config, ContainerBuilder $container): void
    {
        $container->register('.zenstruck_filesystem.doctrine.node_config_provider', ORMNodeConfigProvider::class)
            ->setArguments([new Reference('doctrine.orm.entity_manager')])
        ;
        $container->register('.zenstruck_filesystem.doctrine._cache_node_config_provider', CacheNodeConfigProvider::class)
            ->setDecoratedService('.zenstruck_filesystem.doctrine.node_config_provider')
            ->setArguments([new Reference('.inner'), new Reference('cache.system')])
            ->addTag('kernel.cache_warmer', ['optional' => false])
        ;
        $container->register(ObjectNodeLoader::class)
            ->setArguments([new Reference('.zenstruck_filesystem.filesystem_registry'), new Reference('.zenstruck_filesystem.doctrine.node_config_provider')])
            ->addTag('twig.runtime')
        ;

        if (\class_exists(Environment::class)) {
            $container->register('.zenstruck_filesystem.twig.object_node_loader', ObjectNodeLoaderExtension::class)
                ->addTag('twig.extension')
            ;
        }

        if (!($config['events']['load']['enabled'] || $config['events']['persist']['enabled'] || $config['events']['update']['enabled'] || $config['events']['remove']['enabled'])) {
            return;
        }

        $slugger = \interface_exists(LocaleAwareInterface::class) ? new Reference('slugger', ContainerInterface::NULL_ON_INVALID_REFERENCE) : null;

        $container->register('.zenstruck_filesystem.namer.checksum', ChecksumNamer::class)
            ->addTag('zenstruck_filesystem.doctrine_namer', ['key' => 'checksum'])
        ;
        $container->register('.zenstruck_filesystem.namer.expression', ExpressionNamer::class)
            ->addArgument($slugger)
            ->addTag('zenstruck_filesystem.doctrine_namer', ['key' => 'expression'])
        ;
        $container->register('.zenstruck_filesystem.namer.slugify', SlugifyNamer::class)
            ->addTag('zenstruck_filesystem.doctrine_namer', ['key' => 'slugify'])
            ->addArgument($slugger)
        ;

        if (\class_exists(ExpressionLanguage::class)) {
            $container->register('.zenstruck.filesystem.namer._expression_language', ExpressionLanguage::class)
                ->addArgument(new Reference('cache.system'))
            ;
            $container->register('.zenstruck.filesystem.namer.expression_language', ExpressionLanguageNamer::class)
                ->setArguments([new Reference('.zenstruck.filesystem.namer._expression_language'), $slugger])
                ->addTag('zenstruck_filesystem.doctrine_namer', ['key' => 'expression_language'])
            ;
        }

        if (\class_exists(Environment::class)) {
            $container->register('.zenstruck.filesystem.namer.twig', TwigNamer::class)
                ->setArguments([new Reference('twig'), $slugger])
                ->addTag('zenstruck_filesystem.doctrine_namer', ['key' => 'twig'])
            ;
        }

        $container->register('.zenstruck_filesystem.doctrine.namer_locator', ServiceLocator::class)
            ->addArgument(new TaggedIteratorArgument('zenstruck_filesystem.doctrine_namer', 'key'))
            ->addTag('container.service_locator')
        ;

        $subscriber = $container->register('.zenstruck_filesystem.doctrine.node_event_subscriber', NodeLifecycleSubscriber::class)
            ->setArguments([
                new ServiceLocatorArgument([
                    FilesystemRegistry::class => new Reference('.zenstruck_filesystem.filesystem_registry'),
                    'namers' => new Reference('.zenstruck_filesystem.doctrine.namer_locator'),
                ]),
                new Reference('.zenstruck_filesystem.doctrine.node_config_provider'),
                [
                    NodeConfigProvider::AUTOLOAD => $config['events']['load'][NodeConfigProvider::AUTOLOAD],
                    NodeConfigProvider::WRITE_ON_PERSIST => $config['events']['persist'][NodeConfigProvider::WRITE_ON_PERSIST],
                    NodeConfigProvider::WRITE_ON_UPDATE => $config['events']['update'][NodeConfigProvider::WRITE_ON_UPDATE],
                    NodeConfigProvider::DELETE_ON_UPDATE => $config['events']['update'][NodeConfigProvider::DELETE_ON_UPDATE],
                    NodeConfigProvider::DELETE_ON_REMOVE => $config['events']['remove'][NodeConfigProvider::DELETE_ON_REMOVE],
                ],
            ])
        ;

        $container->registerForAutoconfiguration(Namer::class)
            ->addTag('zenstruck_filesystem.doctrine_namer')
        ;

        if ($config['events']['load']['enabled']) {
            $subscriber->addTag('doctrine.event_listener', ['event' => 'postLoad']);
        }

        if ($config['events']['persist']['enabled']) {
            $subscriber->addTag('doctrine.event_listener', ['event' => 'prePersist']);
        }

        if ($config['events']['update']['enabled']) {
            $subscriber
                ->addTag('doctrine.event_listener', ['event' => 'preUpdate'])
                ->addTag('doctrine.event_listener', ['event' => 'postFlush'])
            ;
        }

        if ($config['events']['remove']['enabled']) {
            $subscriber->addTag('doctrine.event_listener', ['event' => 'postRemove']);
        }
    }

    private function addFilesystem(string $name, array $config, ContainerBuilder $container, string $defaultName): void
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

        $features = [];

        if ($config['url_prefix']) {
            $container->register($id = '.zenstruck_filesystem.feature.'.$name.'_url_prefix', PrefixFileUrlFeature::class)
                ->setArguments([$config['url_prefix']])
            ;

            $features[FileUrl::class] = new Reference($id);
        }

        if ($config['route']['name']) {
            $container->register($id = '.zenstruck_filesystem.feature.'.$name.'_route', RouteFileUrlFeature::class)
                ->setArguments([
                    $config['route'],
                    new ServiceLocatorArgument([
                        UrlGeneratorInterface::class => new Reference(UrlGeneratorInterface::class),
                        UriSigner::class => new Reference(UriSigner::class),
                    ]),
                ])
            ;

            $features[FileUrl::class] = new Reference($id);
        }

        $config['config']['name'] = $name;

        $filesystemDef = $container->register($filesystem = 'zenstruck_filesystem.filesystem.'.$name, AdapterFilesystem::class)
            ->setArguments([$config['dsn'], $config['config'], new ServiceLocatorArgument($features)])
            ->addTag('zenstruck_filesystem', ['key' => $name])
        ;

        if ($config['readonly']) {
            $container->register('.zenstruck_filesystem.filesystem.readonly_'.$name, ReadonlyFilesystem::class)
                ->setDecoratedService($filesystem)
                ->setArguments([new Reference('.inner')])
            ;
        }

        if (\is_string($config['test']) || (null === $config['test'] && 'test' === $container->getParameter('kernel.environment'))) {
            if (null === $config['test']) {
                $config['test'] = '%kernel.project_dir%/var/testfiles%env(default::TEST_TOKEN)%/'.$name;
            }

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

            if (!$container->hasParameter('zenstruck_filesystem.test_filesystems')) {
                $container->setParameter('zenstruck_filesystem.test_filesystems', []);
            }

            $container->setParameter(
                'zenstruck_filesystem.test_filesystems',
                \array_merge($container->getParameter('zenstruck_filesystem.test_filesystems'), [$filesystem])
            );
        }

        if ($config['log']) {
            $container->register('.zenstruck_filesystem.filesystem.log_'.$name, LoggableFilesystem::class)
                ->setDecoratedService($filesystem)
                ->setArguments([new Reference('.inner'), new Reference('logger')])
                ->addTag('monolog.logger', ['channel' => 'filesystem'])
            ;
        }

        if ($container->hasDefinition('.zenstruck_filesystem.data_collector')) {
            $container->register('.zenstruck_filesystem.filesystem.traceable_'.$name, TraceableFilesystem::class)
                ->setDecoratedService($filesystem)
                ->setArguments([new Reference('.inner')])
            ;

            $container->getDefinition('.zenstruck_filesystem.data_collector')
                ->addMethodCall('addFilesystem', [new Reference('.zenstruck_filesystem.filesystem.traceable_'.$name)])
            ;
        }

        if ($name === $defaultName) {
            $container->setAlias(Filesystem::class, $filesystem);
        } else {
            $container->registerAliasForArgument($filesystem, Filesystem::class, $name.'Filesystem');
        }
    }
}
