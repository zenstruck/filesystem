<?php

namespace Zenstruck\Filesystem\Node\File;

use League\Flysystem\UnableToRetrieveMetadata;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;
use Zenstruck\Filesystem\Feature\TransformImageUrl;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Image extends File
{
    protected const IMAGE_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png', 'svg', 'apng', 'avif', 'jfif', 'pjpeg', 'pjp', 'webp'];

    /** @var array{0:int,1:int} */
    private array $imageSize;

    protected function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param array<string,mixed> $options
     *
     * @throws UnsupportedFeature If your adapter does not support {@see TransformImageUrl}
     */
    final public function transformUrl(array $options = []): Uri
    {
        return $this->operator()->transformUrlFor($this, $options);
    }

    /**
     * Alias of transformUrl for most popular use case
     */
    final public function thumbUrl(array $options = []): Uri
    {
        return $this->transformUrl($options);
    }

    final public function height(): int
    {
        return $this->imageSize()[1];
    }

    final public function width(): int
    {
        return $this->imageSize()[0];
    }

    final public function aspectRatio(): float
    {
        return $this->width() / $this->height();
    }

    final public function pixels(): int
    {
        return $this->width() * $this->height();
    }

    final public function isSquare(): bool
    {
        return $this->width() === $this->height();
    }

    final public function isPortrait(): bool
    {
        return $this->height() > $this->width();
    }

    final public function isLandscape(): bool
    {
        return $this->width() > $this->height();
    }

    final public function refresh(): static
    {
        unset($this->imageSize);

        return parent::refresh();
    }

    /**
     * @return array{0:int,1:int}
     */
    private function imageSize(): array
    {
        if (isset($this->imageSize)) {
            return $this->imageSize;
        }

        $file = $this->operator()->realFile($this);

        if ('image/svg+xml' === $this->mimeType()) {
            return $this->imageSize = self::parseSvg($file) ?? throw UnableToRetrieveMetadata::create($this->path(), 'image_metadata', 'Unable to load svg.');
        }

        if (false === $imageSize = @\getimagesize($file)) {
            throw UnableToRetrieveMetadata::create($this->path(), 'image_size');
        }

        return $this->imageSize = $imageSize; // @phpstan-ignore-line
    }

    /**
     * @return null|array{0:int,1:int}
     */
    private static function parseSvg(\SplFileInfo $file): ?array
    {
        if (false === $xml = \file_get_contents($file)) {
            return null;
        }

        if (false === $xml = \simplexml_load_string($xml)) {
            return null;
        }

        if (!$xml = $xml->attributes()) {
            return null;
        }

        return [
            (int) \round((float) $xml->width),
            (int) \round((float) $xml->height),
        ];
    }
}
