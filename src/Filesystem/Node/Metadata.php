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
 * @phpstan-type Format = self::PATH|self::DSN|array<int|self::*,self::*|string|list<string>>
 * @phpstan-type Serialized = string|array<string,scalar|array<string,scalar>>
 */
final class Metadata
{
    public const PATH = 'path';
    public const DSN = 'dsn';
    public const FILENAME = 'filename';
    public const LAST_MODIFIED = 'last_modified';
    public const VISIBILITY = 'visibility';
    public const MIME_TYPE = 'mime_type';
    public const SIZE = 'size';
    public const CHECKSUM = 'checksum';
    public const PUBLIC_URL = 'public_url';
    public const EXTENSION = 'extension';
    public const TRANSFORM_URL = 'transform_url';
    public const DIMENSIONS = 'dimensions';
    public const EXIF = 'exif';
    public const IPTC = 'iptc';

    private const STRING_METADATA = [self::PATH, self::DSN];
    private const NODE_METADATA = [self::PATH, self::DSN, self::LAST_MODIFIED, self::VISIBILITY, self::FILENAME];
    private const FILE_METADATA = [self::SIZE, self::CHECKSUM, self::PUBLIC_URL, self::EXTENSION, self::MIME_TYPE];
    private const IMAGE_METADATA = [self::DIMENSIONS, self::EXIF, self::IPTC];

    private function __construct()
    {
    }

    /**
     * @internal
     *
     * @param class-string<Node> $class
     *
     * @return Format
     */
    public static function validate(string $class, array|string $metadata): array|string
    {
        if (\is_string($metadata)) {
            if (\in_array($metadata, self::STRING_METADATA, true)) {
                return $metadata;
            }

            throw new \LogicException(\sprintf('Metadata "%s" cannot be used as a string.', $metadata));
        }

        if (!$metadata) {
            throw new \LogicException('Metadata cannot be empty.');
        }

        foreach ($metadata as $key => $value) {
            if (\in_array($value, self::NODE_METADATA, true)) {
                continue;
            }

            if (\in_array($value, self::FILE_METADATA, true)) {
                if (!\is_a($class, File::class, true)) {
                    throw new \LogicException(\sprintf('Metadata "%s" can only be used with files.', $value));
                }

                continue;
            }

            if (\in_array($value, self::IMAGE_METADATA, true)) {
                if (!\is_a($class, Image::class, true)) {
                    throw new \LogicException(\sprintf('Metadata "%s" can only be used with images.', $value));
                }

                continue;
            }

            if (self::CHECKSUM === $key) {
                continue;
            }

            if (self::TRANSFORM_URL === $key && (\is_array($value) || \is_string($value))) {
                continue;
            }

            throw new \LogicException(\sprintf('Metadata "%s:%s" is invalid.', $key, $value));
        }

        return $metadata;
    }

    /**
     * @internal
     *
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
                self::FILENAME => $node->path()->name(),
                self::LAST_MODIFIED => $node->lastModified()->format('c'),
                self::VISIBILITY => $node->visibility(),
                self::MIME_TYPE => $node->ensureFile()->mimeType(),
                self::SIZE => $node->ensureFile()->size(),
                self::EXTENSION => $node->path()->extension(),
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
