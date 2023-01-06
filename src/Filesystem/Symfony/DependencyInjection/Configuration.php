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
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('zenstruck_filesystem');

        $treeBuilder->getRootNode() // @phpstan-ignore-line
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
                                    '@my_adapter_service',
                                ])
                            ->end()
                            ->variableNode('config')
                                ->info('Extra global adapter filesystem config')
                                ->defaultValue([])
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
                            ->booleanNode('log') // todo log levels
                                ->defaultTrue()
                                ->info('Whether or not to log filesystem operations')
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
                    ->info('Default filesystem name, if not configured, uses first one defined above')
                ->end()
                ->arrayNode('doctrine')
                    ->{ContainerBuilder::willBeAvailable('doctrine/orm', EntityManagerInterface::class, ['doctrine/doctrine-bundle']) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->arrayNode('lifecycle')
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
