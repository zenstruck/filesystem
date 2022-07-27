<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\DependencyInjection;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
                                ->info('Url prefix or multiple prefixes to use for this filesystem (can be an array)')
                                ->example(['/files', 'https://cdn1.example.com', 'https://cdn2.example.com'])
                            ->end()
                            ->booleanNode('readonly')
                                ->defaultFalse()
                                ->info('Set to true to create a "readonly" filesystem (write operations will fail)')
                            ->end()
                            ->booleanNode('log') // todo log levels
                                ->defaultTrue()
                                ->info('Whether or not to log filesystem operations')
                            ->end()
                            ->scalarNode('test')
                                ->validate()
                                    ->ifTrue(fn($v) => !\is_string($v) && false !== $v)
                                    ->thenInvalid('%s is invalid, must be either string or false')
                                ->end()
                                ->defaultNull()
                                ->info(<<<EOF
                                    If false, disable
                                    If string, use as filesystem DSN and swap real adapter with this
                                    Defaults to false in env's other than "test"
                                    If not explicitly configured, in "test" env, defaults to "var/testfiles"
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
                ->arrayNode('doctrine')
                    ->{\interface_exists(ManagerRegistry::class) ? 'canBeDisabled' : 'canBeEnabled'}()
                    ->children()
                        ->arrayNode('events')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('load')
                                    ->defaultTrue()
                                    ->info('Whether to load filesystem files by default')
                                ->end()
                                ->booleanNode('persist')
                                    ->defaultTrue()
                                    ->info('Whether to save filesystem files on entity persist')
                                ->end()
                                ->booleanNode('update')
                                    ->defaultTrue()
                                    ->info('Whether to save filesystem files on entity update')
                                ->end()
                                ->booleanNode('delete')
                                    ->defaultTrue()
                                    ->info('Whether to delete filesystem files on entity remove')
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
