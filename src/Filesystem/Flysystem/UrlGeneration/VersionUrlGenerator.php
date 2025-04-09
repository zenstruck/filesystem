<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Flysystem\UrlGeneration;

use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Uri\ParsedUri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class VersionUrlGenerator implements PublicUrlGenerator
{
    /**
     * @param Mapping::LAST_MODIFIED|Mapping::SIZE|Mapping::CHECKSUM $metadata
     */
    public function __construct(
        private PublicUrlGenerator $inner,
        private string $metadata = Mapping::LAST_MODIFIED,
        private string $queryParameter = 'v',
    ) {
        if (!\class_exists(ParsedUri::class)) {
            throw new \LogicException('zenstruck\filesystem requires zenstruck/uri to use versioned public URLs. Install with "composer require zenstruck/uri".');
        }
    }

    public function publicUrl(string $path, Config $config): string
    {
        if (false === ($version = $config->get('version') ?? $this->metadata)) {
            return $this->inner->publicUrl($path, $config);
        }

        $file = $config->get('_file');

        if (!$file instanceof File) {
            throw new \LogicException(\sprintf('"%s::publicUrl()" requires the "_file" option to be set to a "%s" instance.', self::class, File::class));
        }

        $value = match ($version) {
            Mapping::LAST_MODIFIED => $file->lastModified()->getTimestamp(),
            Mapping::SIZE => $file->size(),
            Mapping::CHECKSUM => $file->checksum(),
            default => throw new \InvalidArgumentException(\sprintf('Unknown version "%s".', $version)),
        };

        return ParsedUri::new($this->inner->publicUrl($path, $config))
            ->withQueryParam($this->queryParameter, $value)
            ->toString()
        ;
    }
}
