<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Doctrine\Twig;

use Twig\Environment;
use Zenstruck\Tests\Filesystem\Doctrine\DoctrineTestCase;
use Zenstruck\Tests\Fixtures\Entity\Entity2;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileMappingLoaderExtensionTest extends DoctrineTestCase
{
    /**
     * @test
     */
    public function render(): void
    {
        $this->filesystem()->write('foo.txt', 'content');

        $rendered = self::getContainer()->get(Environment::class)->render('file_mapping_loader.html.twig', [
            'object' => new Entity2('Foo'),
        ]);

        $this->assertSame("9a0364b9e99bb480dd25e1f0284c8555\n7\n", $rendered);
    }
}
