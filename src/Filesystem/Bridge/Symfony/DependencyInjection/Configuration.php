<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Configuration implements ConfigurationInterface
{
    public function __construct(private string $env)
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('zenstruck_filesystem');

        $treeBuilder->getRootNode()
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
                                ->info('Filesystem adapter DSN or, if prefixed with "@" filesystem adapter service id')
                                ->example('%kernel.project_dir%/public/files OR @my_adapter_service')
                            ->end()
                            ->booleanNode('readonly')
                                ->defaultFalse()
                                ->info('Set to true to create a "readonly" filesystem (write operations will fail)')
                            ->end()
                            ->variableNode('log') // todo log levels
                                ->defaultTrue()
                                ->info('Whether or not to log filesystem operations')
                            ->end()
                            ->booleanNode('trace') // todo
                                ->defaultValue('%kernel.debug%')
                                ->info('Whether or not to track filesystem operations with the profiler')
                            ->end()
                            ->booleanNode('test') // todo swap with static-in-memory
                                ->defaultValue('test' === $this->env)
                                ->info('When true, swaps the real filesystem adapter with an in-memory one, defaults to true in your test env')
                            ->end()
                            ->variableNode('config')
                                ->defaultValue([])
                                ->info('Extra global adapter filesystem config')
                                ->example(['image_check_mime' => true])
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_filesystem')
                    ->defaultNull()
                    ->info('Default filesystem name, if not configured, use first one')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
