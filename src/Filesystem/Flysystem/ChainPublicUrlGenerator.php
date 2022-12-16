<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Flysystem;

use League\Flysystem\Config;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ChainPublicUrlGenerator implements PublicUrlGenerator
{
    /**
     * @param PublicUrlGenerator[] $generators
     */
    public function __construct(private iterable $generators)
    {
    }

    public function publicUrl(string $path, Config $config): string
    {
        foreach ($this->generators as $generator) {
            try {
                return $generator->publicUrl($path, $config);
            } catch (UnableToGeneratePublicUrl) {
                continue;
            }
        }

        throw UnableToGeneratePublicUrl::noGeneratorConfigured($path, 'No generator in the chain supports.');
    }
}
