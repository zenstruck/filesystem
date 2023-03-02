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

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\VarExporter\LazyObjectInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Operation;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('zenstruck_filesystem');

        $treeBuilder->getRootNode() // @phpstan-ignore-line
            ->validate()
                ->ifTrue(function(array $v) {
                    if (null === $v['default_filesystem']) {
                        return false;
                    }

                    return !\array_key_exists($v['default_filesystem'], $v['filesystems']);
                })
                ->thenInvalid('The default_filesystem is not configured')
            ->end()
            ->children()
                ->arrayNode('filesystems')
                    ->info('Filesystem configurations')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(fn($v) => ['dsn' => $v])
                        ->end()
                        ->children()
                            ->scalarNode('dsn')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('Flysystem adapter DSN or, if prefixed with "@" flysystem adapter service id')
                                ->example([
                                    '%kernel.project_dir%/public/files',
                                    'ftp://foo:bar@example.com/path',
                                    's3://accessKeyId:accessKeySecret@bucket/prefix#us-east-1',
                                    'static-in-memory',
                                    'scoped:<name>:<prefix>',
                                    '@my_adapter_service',
                                ])
                            ->end()
                            ->variableNode('config')
                                ->info('Extra global adapter filesystem config')
                                ->defaultValue([])
                            ->end()
                            ->booleanNode('lazy')
                                ->info('Lazily load the filesystem when the first call occurs (requires Symfony 6.2+)')
                                ->validate()
                                    ->ifTrue(fn($v) => $v && !\interface_exists(LazyObjectInterface::class))
                                    ->thenInvalid('symfony/var-exporter 6.2+ is required')
                                ->end()
                                ->defaultValue(\interface_exists(LazyObjectInterface::class))
                            ->end()
                            ->arrayNode('public_url')
                                ->info('Public URL generator for this filesystem')
                                ->beforeNormalization()
                                ->ifString()
                                ->then(function(string $v) {
                                    return match (true) {
                                        \str_starts_with($v, 'route:') => ['route' => ['name' => \mb_substr($v, 6)]],
                                        \str_starts_with($v, '@') => ['service' => \mb_substr($v, 1)],
                                        default => ['prefix' => $v],
                                    };
                                })
                                ->end()
                                ->validate()
                                    ->ifTrue(fn($v) => \count(\array_filter($v)) > 1)
                                    ->thenInvalid('Can only set one of "prefix", "service", "route"')
                                ->end()
                                ->children()
                                    ->variableNode('prefix')
                                        ->info('URL prefix or multiple prefixes to use for this filesystem (can be an array)')
                                        ->example(['/files', 'https://cdn1.example.com', 'https://cdn2.example.com'])
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('service')
                                        ->info('Service id for a '.PublicUrlGenerator::class)
                                        ->defaultNull()
                                    ->end()
                                    ->arrayNode('route')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(fn($v) => ['name' => $v])
                                        ->end()
                                        ->info('Generate with a route')
                                        ->children()
                                            ->scalarNode('name')
                                                ->info('Route name')
                                                ->isRequired()
                                                ->cannotBeEmpty()
                                            ->end()
                                            ->variableNode('parameters')
                                                ->info('Route parameters')
                                                ->defaultValue([])
                                            ->end()
                                            ->booleanNode('sign')
                                                ->info('Sign by default?')
                                                ->defaultFalse()
                                            ->end()
                                            ->scalarNode('expires')
                                                ->info('Default expiry')
                                                ->example('+ 30 minutes')
                                                ->defaultNull()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('temporary_url')
                                ->info('Temporary URL generator for this filesystem')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function(string $v) {
                                        return match (true) {
                                            \str_starts_with($v, 'route:') => ['route' => ['name' => \mb_substr($v, 6)]],
                                            \str_starts_with($v, '@') => ['service' => \mb_substr($v, 1)],
                                            default => ['service' => $v],
                                        };
                                    })
                                ->end()
                                ->validate()
                                    ->ifTrue(fn($v) => \count(\array_filter($v)) > 1)
                                    ->thenInvalid('Can only set one of "service", "route"')
                                ->end()
                                ->children()
                                    ->scalarNode('service')
                                        ->info('Service id for a '.TemporaryUrlGenerator::class)
                                        ->defaultNull()
                                    ->end()
                                    ->arrayNode('route')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(fn($v) => ['name' => $v])
                                        ->end()
                                        ->info('Generate with a route')
                                        ->children()
                                            ->scalarNode('name')
                                                ->info('Route name')
                                                ->isRequired()
                                                ->cannotBeEmpty()
                                            ->end()
                                                ->variableNode('parameters')
                                                ->info('Route parameters')
                                                ->defaultValue([])
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('image_url')
                                ->info('Image Transform URL generator for this filesystem')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function(string $v) {
                                        return match (true) {
                                            \str_starts_with($v, 'route:') => ['route' => ['name' => \mb_substr($v, 6)]],
                                            \str_starts_with($v, '@') => ['service' => \mb_substr($v, 1)],
                                            default => ['service' => $v],
                                        };
                                    })
                                ->end()
                                ->validate()
                                    ->ifTrue(fn($v) => \count(\array_filter($v)) > 1)
                                    ->thenInvalid('Can only set one of "service", "route"')
                                ->end()
                                ->children()
                                    ->scalarNode('service')
                                        ->info('Service id for a '.PublicUrlGenerator::class)
                                        ->defaultNull()
                                    ->end()
                                    ->arrayNode('route')
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(fn($v) => ['name' => $v])
                                        ->end()
                                        ->info('Generate with a route')
                                        ->children()
                                            ->scalarNode('name')
                                                ->info('Route name')
                                                ->isRequired()
                                                ->cannotBeEmpty()
                                            ->end()
                                            ->variableNode('parameters')
                                                ->info('Route parameters')
                                                ->defaultValue([])
                                            ->end()
                                            ->booleanNode('sign')
                                                ->info('Sign by default?')
                                                ->defaultFalse()
                                            ->end()
                                            ->scalarNode('expires')
                                                ->info('Default expiry')
                                                ->example('+ 30 minutes')
                                                ->defaultNull()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('events')
                                ->info('Dispatch filesystem operation events')
                                ->canBeEnabled()
                                ->children()
                                    ->booleanNode(Operation::WRITE)->defaultTrue()->end()
                                    ->booleanNode(Operation::DELETE)->defaultTrue()->end()
                                    ->booleanNode(Operation::MKDIR)->defaultTrue()->end()
                                    ->booleanNode(Operation::CHMOD)->defaultTrue()->end()
                                    ->booleanNode(Operation::COPY)->defaultTrue()->end()
                                    ->booleanNode(Operation::MOVE)->defaultTrue()->end()
                                ->end()
                            ->end()
                            ->arrayNode('log')
                                ->info('Log filesystem operations')
                                ->canBeDisabled()
                                ->children()
                                    ->enumNode(Operation::READ)
                                        ->values($levels = [false, LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG])
                                        ->defaultValue(LogLevel::DEBUG)
                                    ->end()
                                    ->enumNode(Operation::WRITE)
                                        ->values($levels)
                                        ->defaultValue(LogLevel::INFO)
                                    ->end()
                                    ->enumNode(Operation::MOVE)
                                        ->values($levels)
                                    ->end()
                                    ->enumNode(Operation::COPY)
                                        ->values($levels)
                                    ->end()
                                    ->enumNode(Operation::DELETE)
                                        ->values($levels)
                                    ->end()
                                    ->enumNode(Operation::CHMOD)
                                        ->values($levels)
                                    ->end()
                                    ->enumNode(Operation::MKDIR)
                                        ->values($levels)
                                    ->end()
                                ->end()
                            ->end()
                            ->booleanNode('temporary')
                                ->defaultFalse()
                                ->info(<<<EOF
                                    If true, this filesystem will be configured to store
                                    uploaded files in a serializable way.
                                    EOF)
                            ->end()
                            ->booleanNode('reset_before_tests')
                                ->defaultFalse()
                                ->info(<<<EOF
                                    If true, and using the ResetFilesystem trait
                                    in your KernelTestCase's, delete this filesystem
                                    before each test.
                                    EOF)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_filesystem')
                    ->defaultNull()
                    ->info('Default filesystem name used to autowire '.Filesystem::class)
                ->end()
                ->arrayNode('doctrine')
                    ->{ContainerBuilder::willBeAvailable('doctrine/orm', EntityManagerInterface::class, ['doctrine/doctrine-bundle']) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->info('Doctrine configuration')
                    ->children()
                        ->arrayNode('lifecycle')
                            ->info('Global lifecycle events (can be disabled on a property-by-property basis)')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('autoload')
                                    ->info('Whether to auto load file type columns during object load')
                                    ->defaultTrue()
                                ->end()
                                ->booleanNode('delete_on_remove')
                                    ->info('Whether to delete files on object removal')
                                    ->defaultTrue()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
