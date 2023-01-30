<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Fixture;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Path\Generator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CustomObjectPathGenerator implements Generator
{
    public function generatePath(File $file, array $context = []): string
    {
        return \sprintf('images/%s-%s.%s',
            \mb_strtolower((new AsciiSlugger())->slug($context['this']->getTitle())),
            \mb_substr($file->checksum(), 0, 7),
            $file->path()->extension(),
        );
    }
}
