<?php

namespace Zenstruck\Filesystem\Tests\Fixture\Symfony;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Zenstruck\Filesystem\Bridge\Symfony\ZenstruckFilesystemBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new TwigBundle();
        yield new ZenstruckFoundryBundle();
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

        $c->loadFromExtension('zenstruck_filesystem', [
            'filesystems' => [
                'public' => '%kernel.project_dir%/var/public',
                'private' => '%kernel.project_dir%/var/private',
            ],
        ]);

        $c->register(Service::class)
            ->setPublic(true)
            ->setAutowired(true)
        ;
    }
}
