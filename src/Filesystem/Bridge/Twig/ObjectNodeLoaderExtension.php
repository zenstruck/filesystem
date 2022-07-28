<?php

namespace Zenstruck\Filesystem\Bridge\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Zenstruck\Filesystem\Bridge\Doctrine\ObjectNodeLoader;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ObjectNodeLoaderExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('load_object_files', [ObjectNodeLoader::class, 'load']),
        ];
    }
}
