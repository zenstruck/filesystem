<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node;

use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @TODO make enum in PHP 8.1
 *
 * @phpstan-type Format = self::PATH|self::DSN|array<int|self::*,self::*|list<string>>
 * @phpstan-type Serialized = string|array<string,scalar|array<string,scalar>>
 */
final class Metadata
{
    public const PATH = 'path';
    public const DSN = 'dsn';
    public const LAST_MODIFIED = 'last_modified';
    public const VISIBILITY = 'visibility';
    public const MIME_TYPE = 'mime_type';
    public const SIZE = 'size';
    public const CHECKSUM = 'checksum';
    public const PUBLIC_URL = 'public_url';
    public const TRANSFORM_URL = 'transform_url';
    public const DIMENSIONS = 'dimensions';
    public const EXIF = 'exif';
    public const IPTC = 'iptc';

    private function __construct()
    {
    }

    /**
     * @param Format $metadata
     *
     * @return Serialized
     */
    public static function serialize(Node $node, string|array $metadata): string|array
    {
        if (self::PATH === $metadata) {
            return $node->path();
        }

        if (self::DSN === $metadata) {
            return $node->dsn();
        }

        $ret = [];

        foreach ($metadata as $key => $value) {
            if (\is_int($key) && \is_string($value)) {
                $key = $value;
            }

            $ret[$key] = match ($key) {
                self::PATH => $node->path()->toString(),
                self::DSN => $node->dsn()->toString(),
                self::LAST_MODIFIED => $node->lastModified()->format('c'),
                self::VISIBILITY => $node->visibility(),
                self::MIME_TYPE => $node->mimeType(),
                self::SIZE => $node->ensureFile()->size(),
                self::CHECKSUM => self::serializeChecksum($node->ensureFile(), $value),
                self::PUBLIC_URL => $node->ensureFile()->publicUrl(),
                self::TRANSFORM_URL => self::serializeTransformUrl($node->ensureImage(), $value),
                self::DIMENSIONS => $node->ensureImage()->dimensions()->jsonSerialize(),
                self::EXIF => $node->ensureImage()->exif(),
                self::IPTC => $node->ensureImage()->iptc(),
                default => throw new \InvalidArgumentException('Invalid metadata definition.'), // todo
            };
        }

        return $ret; // @phpstan-ignore-line
    }

    /**
     * @param string|list<string> $value
     *
     * @return array<string,string>
     */
    private static function serializeTransformUrl(Image $image, string|array $value): array
    {
        $ret = [];

        foreach ((array) $value as $filter) {
            $ret[$filter] = $image->transformUrl($filter);
        }

        return $ret;
    }

    /**
     * @param string|list<string> $value
     *
     * @return string|array<string,string>
     */
    private static function serializeChecksum(File $file, string|array $value): string|array
    {
        if (self::CHECKSUM === $value) {
            return $file->checksum();
        }

        $ret = [];

        foreach ((array) $value as $algo) {
            $ret[$algo] = $file->checksum($algo);
        }

        return $ret;
    }
}
