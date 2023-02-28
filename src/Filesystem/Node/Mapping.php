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
use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Node\Path\Namer;
use Zenstruck\Filesystem\Twig\Template;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @readonly
 *
 * @phpstan-type Format = self::PATH|self::DSN|self::FILENAME|array<int|self::*,self::*|string|list<string>>
 * @phpstan-type Serialized = string|array<string,scalar|array<string,scalar>>
 */
class Mapping
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

    /** @var Format */
    public string|array $metadata;

    private ?Namer $namer;

    /**
     * @param Format $metadata
     */
    public function __construct(
        string|array $metadata,
        private ?string $filesystem = null,
        string|Namer|null $namer = null,
    ) {
        $this->metadata = $metadata;
        $this->namer = self::parseNamer($namer);

        if (!$this->filesystem && $this->requiresFilesystem()) {
            throw new \LogicException('A filesystem is required if not serializing the DSN.');
        }

        if (!$this->namer && $this->requiresPathGenerator()) {
            throw new \LogicException('A namer is required if not serializing the DSN or path.');
        }
    }

    /**
     * @internal
     */
    public static function fromArray(array $array): self
    {
        $filesystem = $array['filesystem'] ?? null;

        if ($filesystem instanceof self) {
            return $filesystem;
        }

        return new self(
            $array['metadata'] ?? (isset($array['filesystem']) ? self::PATH : self::DSN),
            $filesystem,
            $array['namer'] ?? null,
        );
    }

    /**
     * @internal
     */
    public function filesystem(): ?string
    {
        return $this->filesystem;
    }

    /**
     * @internal
     */
    public function namer(): ?Namer
    {
        return $this->namer;
    }

    /**
     * @internal
     */
    final public function requiresPathGenerator(): bool
    {
        $metadata = (array) $this->metadata;

        return !\in_array(self::DSN, $metadata, true) && !\in_array(self::PATH, $metadata, true);
    }

    /**
     * @internal
     *
     * @param class-string<Node> $class
     *
     * @return Format
     */
    public function validate(string $class): array|string
    {
        if (\is_string($this->metadata)) {
            if (\in_array($this->metadata, self::STRING_METADATA, true)) {
                return $this->metadata;
            }

            throw new \LogicException(\sprintf('Metadata "%s" cannot be used as a string.', $this->metadata));
        }

        if (!$this->metadata) {
            throw new \LogicException('Metadata cannot be empty.');
        }

        foreach ($this->metadata as $key => $value) {
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

            throw new \LogicException(\sprintf('Metadata "%s:%s" is invalid.', $key, \is_array($value) ? \json_encode($value) : $value));
        }

        return $this->metadata;
    }

    /**
     * @internal
     *
     * @return Serialized
     */
    public function serialize(Node $node): string|array
    {
        if (self::PATH === $this->metadata) {
            return $node->path();
        }

        if (self::DSN === $this->metadata) {
            return $node->dsn();
        }

        if (self::FILENAME === $this->metadata) {
            return $node->path()->name();
        }

        if (\is_string($this->metadata)) {
            throw new \InvalidArgumentException(\sprintf('Unable to serialize node "%s" with metadata "%s".', $node->dsn(), $this->metadata));
        }

        $ret = [];

        foreach ($this->metadata as $key => $value) {
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

    private function requiresFilesystem(): bool
    {
        if (self::DSN === $this->metadata) {
            return false;
        }

        if (\is_array($this->metadata) && \in_array(self::DSN, $this->metadata, true)) {
            return false;
        }

        return true;
    }

    private static function parseNamer(string|Namer|null $namer): ?Namer
    {
        if (null === $namer || $namer instanceof Namer) {
            return $namer;
        }

        if (2 === \count($parts = \explode(':', $namer, 2))) {
            return match ($parts[0]) {
                'expression' => new Expression($parts[1]),
                'twig' => new Template($parts[1]),
                default => throw new \InvalidArgumentException(\sprintf('Unable to parse namer "%s".', $namer)),
            };
        }

        if (\class_exists($namer)) {
            return new Namer($namer);
        }

        if (\str_starts_with($namer, '@')) {
            return new Namer(\mb_substr($namer, 1));
        }

        throw new \InvalidArgumentException(\sprintf('Unable to parse namer "%s".', $namer));
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
