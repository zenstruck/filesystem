<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Twig;

use Twig\Environment;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Path\Generator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TwigPathGenerator implements Generator
{
    public function __construct(private Environment $twig)
    {
    }

    public function generatePath(File $file, array $context = []): string
    {
        if (!isset($context['template'])) {
            throw new \LogicException(\sprintf('A "template" context must be set to use "%s".', self::class));
        }

        $template = (string) $context['template'];
        $context['file'] = $file;

        if (\str_ends_with($template, '.twig')) {
            // template file
            return \trim($this->twig->render($template, $context));
        }

        // inline template
        return $this->twig->createTemplate($template)->render($context);
    }
}
