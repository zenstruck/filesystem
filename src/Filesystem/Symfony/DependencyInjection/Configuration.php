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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
                            ->variableNode('url_prefix')
                                ->defaultNull()
                                ->info('Url prefix or multiple prefixes to use for this filesystem (can be an array)')
                                ->example(['/files', 'https://cdn1.example.com', 'https://cdn2.example.com'])
                            ->end()
                            ->variableNode('config')
                                ->defaultValue([])
                                ->info('Extra global adapter filesystem config')
                            ->end()
                            ->booleanNode('log') // todo log levels
                                ->defaultTrue()
                                ->info('Whether or not to log filesystem operations')
                            ->end()
                            ->booleanNode('reset_before_tests')
                                ->defaultFalse()
                                ->info(<<<EOF
                                    If true, and using the InteractsWithFilesystem trait
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
            ->end()
        ;

        return $treeBuilder;
    }
}
