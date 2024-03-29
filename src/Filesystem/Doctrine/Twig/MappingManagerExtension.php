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
use Zenstruck\Filesystem\Doctrine\MappingManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MappingManagerExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('load_files', [MappingManager::class, 'load']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('load_files', [MappingManager::class, 'load']),
        ];
    }
}
