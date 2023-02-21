<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\PathGenerator;
use Zenstruck\Filesystem\Twig\Template;
use Zenstruck\Filesystem\Twig\TwigPathGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TwigPathGeneratorTest extends KernelTestCase
{
    /**
     * @test
     */
    public function can_render_template(): void
    {
        $file = in_memory_filesystem()->write('some/file.txt', 'content')->ensureFile();

        $this->assertSame('inline/file.txt', $this->generate('inline/{{ file.path.basename }}.{{ file.path.extension }}', $file));
        $this->assertSame('from-file/file.txt', $this->generate('can_render_template.twig', $file));
    }

    private function generate(string $template, File $file): string
    {
        return (new PathGenerator(['twig' => new TwigPathGenerator(self::getContainer()->get('twig'))]))
            ->generate(new Template($template), $file)
        ;
    }
}
