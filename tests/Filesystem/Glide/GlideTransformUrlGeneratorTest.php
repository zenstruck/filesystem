<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Glide;

use League\Flysystem\Config;
use League\Glide\Urls\UrlBuilderFactory;
use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Glide\GlideTransformUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GlideTransformUrlGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function transform_url(): void
    {
        $generator = new GlideTransformUrlGenerator(UrlBuilderFactory::create(''));
        $config = new Config();

        $this->assertSame('/foo?p=bar', $generator->transformUrl('/foo', 'bar', $config));
        $this->assertSame('/foo?p=bar%2Cbaz', $generator->transformUrl('/foo', 'bar,baz', $config));
        $this->assertSame('/foo?p=bar%2Cbaz', $generator->transformUrl('/foo', ['bar', 'baz'], $config));
        $this->assertSame('/foo?w=100&h=200', $generator->transformUrl('/foo', ['w' => 100, 'h' => 200], $config));
    }
}
