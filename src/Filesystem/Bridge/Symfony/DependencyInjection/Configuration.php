<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Zenstruck\Filesystem\Adapter\StaticInMemoryAdapter;

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
                            ->variableNode('url_prefix')
                                ->defaultNull()
                                ->info('Url prefix or multiple prefixes to use for this filesystem')
                                ->example(['/files', ['https://cdn1.example.com', 'https://cdn1.example.com']])
                            ->end()
                            ->booleanNode('readonly')
                                ->defaultFalse()
                                ->info('Set to true to create a "readonly" filesystem (write operations will fail)')
                            ->end()
                            ->variableNode('log') // todo log levels
                                ->defaultTrue()
                                ->info('Whether or not to log filesystem operations')
                            ->end()
                            ->scalarNode('test')
                                ->validate()
                                    ->ifTrue(fn($v) => !\is_string($v) && false !== $v)
                                    ->thenInvalid('%s is invalid, must be either string or false')
                                ->end()
                                ->defaultValue($this->defaultTestValue())
                                ->info(<<<EOF
                                        If false, disable
                                        If string, use as filesystem DSN and swap real adapter with this
                                        Defaults to false in "test" env
                                        If not configured and in "test" env:
                                            1. If league/flysystem-memory is available, swap real adapter with static in-memory one
                                            2. Swaps real adapter with local filesystem in var/testfiles
                                        EOF)
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

    private function defaultTestValue(): string|bool
    {
        if ('test' !== $this->env) {
            return false;
        }

        return StaticInMemoryAdapter::isSupported() ? 'in-memory:?static' : '%kernel.project_dir%/var/testfiles%env(default::TEST_TOKEN)%';
    }
}
