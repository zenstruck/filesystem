<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Routing;

use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Zenstruck\Filesystem\Symfony\Routing\RoutePublicUrlGenerator;
use Zenstruck\Filesystem\Symfony\Routing\RouteTransformUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RoutePublicUrlGeneratorTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @test
     */
    public function configured_expiration_is_applied_to_signed_urls(): void
    {
        if (Kernel::VERSION_ID < 70100) {
            $this->markTestSkipped('Expiring URLs require Symfony 7.1 or higher.');
        }

        $container = $this->createContainer();
        $signer = $container->get(UriSigner::class);
        $expiration = new \DateTimeImmutable('+1 hour');
        $generator = new RoutePublicUrlGenerator($container, 'file', defaultExpires: $expiration->format(\DATE_ATOM));

        $url = $generator->publicUrl('file.txt', new Config());
        \parse_str(\parse_url($url, \PHP_URL_QUERY), $query);

        $this->assertSame((string) $expiration->getTimestamp(), $query['_expiration']);
        $this->assertTrue($signer->check($url));
    }

    /**
     * @test
     *
     * @dataProvider signedUrlGenerators
     * @group legacy
     */
    public function omitted_expiration_is_deprecated(string $generatorClass): void
    {
        $container = $this->createContainer();
        $generator = new $generatorClass($container, 'file', signByDefault: true);
        $earliest = (new \DateTimeImmutable('+1 year'))->getTimestamp();

        $this->expectDeprecation('Generating a signed URL without an expiration is deprecated. Configure a default expiry or pass the "expires" option.');

        $url = $this->generate($generator);
        $latest = (new \DateTimeImmutable('+1 year'))->getTimestamp();
        $signer = $container->get(UriSigner::class);
        $valid = $signer->check($url);

        $this->assertTrue($valid);

        \parse_str(\parse_url($url, \PHP_URL_QUERY), $query);

        if (Kernel::VERSION_ID < 70100) {
            $this->assertArrayNotHasKey('_expiration', $query);

            return;
        }

        $this->assertGreaterThanOrEqual($earliest, (int) $query['_expiration']);
        $this->assertLessThanOrEqual($latest, (int) $query['_expiration']);
    }

    public static function signedUrlGenerators(): iterable
    {
        yield [RoutePublicUrlGenerator::class];
        yield [RouteTransformUrlGenerator::class];
    }

    private function createContainer(): Container
    {
        $routes = new RouteCollection();
        $routes->add('file', new Route('/{path}'));
        $container = new Container();
        $container->set(UrlGeneratorInterface::class, new UrlGenerator($routes, new RequestContext()));
        $container->set(UriSigner::class, new UriSigner('secret'));

        return $container;
    }

    private function generate(RoutePublicUrlGenerator|RouteTransformUrlGenerator $generator): string
    {
        if ($generator instanceof RoutePublicUrlGenerator) {
            return $generator->publicUrl('file.txt', new Config());
        }

        return $generator->transformUrl('file.txt', ['w' => 100], new Config());
    }
}
