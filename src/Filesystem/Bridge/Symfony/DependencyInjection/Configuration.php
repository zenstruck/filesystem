<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\DependencyInjection;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;

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
                            ->scalarNode('glide_url_builder')
                                ->defaultNull()
                                ->info('Glide URL builder service to be used for image previews')
                                ->example([
                                    'League\Glide\Urls\UrlBuilder',
                                    'my_urlbuilder_service'
                                ])
                            ->end()
                            ->arrayNode('route')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(fn($v) => ['name' => $v])
                                ->end()
                                ->addDefaultsIfNotSet()
                                ->info('Route to use for file urls')
                                ->children()
                                    ->scalarNode('name')
                                        ->defaultNull()
                                        ->info('The route name')
                                    ->end()
                                    ->booleanNode('sign')
                                        ->defaultFalse()
                                        ->info('Signed?')
                                    ->end()
                                    ->scalarNode('expires')
                                        ->defaultNull()
                                        ->info('Expire the link after x seconds or a relative datetime string (requires zenstruck/signed-url-bundle)')
                                        ->example(['1800', '+1 day'])
                                    ->end()
                                    ->enumNode('reference_type')
                                        ->values([UrlGeneratorInterface::ABSOLUTE_PATH, UrlGeneratorInterface::ABSOLUTE_URL, UrlGeneratorInterface::NETWORK_PATH, UrlGeneratorInterface::RELATIVE_PATH])
                                        ->cannotBeEmpty()
                                        ->defaultValue(UrlGeneratorInterface::ABSOLUTE_URL)
                                    ->end()
                                    ->variableNode('parameters')
                                        ->defaultValue([])
                                    ->end()
                                ->end()
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
                                ->arrayNode('load')
                                    ->info('Whether to register the post-load event listener')
                                    ->canBeDisabled()
                                    ->children()
                                        ->booleanNode(NodeConfigProvider::AUTOLOAD)
                                            ->defaultTrue()
                                            ->info('Whether to load filesystem files by default')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('persist')
                                    ->info('Whether to register the post-persist event listener')
                                    ->canBeDisabled()
                                    ->children()
                                        ->booleanNode(NodeConfigProvider::WRITE_ON_PERSIST)
                                            ->defaultTrue()
                                            ->info('Whether to write pending filesystem files on entity persist')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('update')
                                    ->info('Whether to register the pre/post-update event listeners')
                                    ->canBeDisabled()
                                    ->children()
                                        ->booleanNode(NodeConfigProvider::WRITE_ON_UPDATE)
                                            ->defaultTrue()
                                            ->info('Whether to save pending filesystem files on entity update')
                                        ->end()
                                        ->booleanNode(NodeConfigProvider::DELETE_ON_UPDATE)
                                            ->defaultTrue()
                                            ->info('Whether to delete removed filesystem files on entity update')
                                        ->end()
                                    ->end()
                                ->end()
                                ->arrayNode('remove')
                                    ->info('Whether to register the post-remove event listener')
                                    ->canBeDisabled()
                                    ->children()
                                        ->booleanNode(NodeConfigProvider::DELETE_ON_REMOVE)
                                            ->defaultTrue()
                                            ->info('Whether to delete filesystem files on entity remove by default')
                                        ->end()
                                    ->end()
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
