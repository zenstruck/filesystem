<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Zenstruck\Filesystem\Doctrine\MappingContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MappingContextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('load_files', [MappingContext::class, '__invoke']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('load_files', [MappingContext::class, '__invoke']),
        ];
    }
}
