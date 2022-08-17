<?php

namespace Zenstruck\Filesystem\Tests\Fixture\Symfony;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use League\Glide\Urls\UrlBuilder;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zenstruck\Filesystem\Bridge\Symfony\ZenstruckFilesystemBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;
use Zenstruck\ZenstruckSignedUrlBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function private(string $path): Response
    {
        return new Response($path);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new TwigBundle();
        yield new ZenstruckFoundryBundle();
        yield new ZenstruckSignedUrlBundle();
        yield new ZenstruckFilesystemBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'router' => ['utf8' => true],
            'test' => true,
        ]);

        $c->loadFromExtension('zenstruck_foundry', [
            'auto_refresh_proxies' => true,
        ]);

        $c->loadFromExtension('doctrine', [
            'dbal' => ['url' => 'sqlite:///%kernel.project_dir%/var/data.db'],
            'orm' => [
                'auto_generate_proxy_classes' => true,
                'auto_mapping' => true,
                'mappings' => [
                    'Test' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/tests/Fixture/Symfony/Entity',
                        'prefix' => 'Zenstruck\Filesystem\Tests\Fixture\Symfony\Entity',
                        'alias' => 'Test',
                    ],
                ],
            ],
        ]);

        $c->loadFromExtension('twig', [
            'default_path' => __DIR__.'/templates',
        ]);

        $c->register(UrlBuilder::class)
            ->setArguments(['/glide/'])
        ;

        $c->loadFromExtension('zenstruck_filesystem', [
            'filesystems' => [
                'public' => [
                    'dsn' => '%kernel.project_dir%/var/public',
                    'url_prefix' => '/files',
                    'glide_url_builder' => UrlBuilder::class,
                ],
                'private' => [
                    'dsn' => '%kernel.project_dir%/var/private',
                    'route' => [
                        'name' => 'private',
                        'sign' => true,
                    ],
                ],
            ],
        ]);

        $c->register(Service::class)
            ->setPublic(true)
            ->setAutowired(true)
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('private', '/some/prefix/{path<.+>}');
    }
}
