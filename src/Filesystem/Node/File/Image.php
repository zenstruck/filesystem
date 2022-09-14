<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem\Feature\ImageTransformer;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 *
 * @phpstan-import-type TransformOptions from ImageTransformer
 */
interface Image extends File
{
    /**
     * @param callable(object):object $manipulator
     * @param TransformOptions        $options
     */
    public function transform(callable $manipulator, array $options = []): PendingImage;

    public function height(): int;

    public function width(): int;

    public function aspectRatio(): float;

    public function pixels(): int;

    public function isSquare(): bool;

    public function isPortrait(): bool;

    public function isLandscape(): bool;

    /**
     * Returns a flattened array of exif data in the format of
     * ["<lowercase-top-level-key>.<key>" => "<value>"].
     *
     * @example ["file.MimeType" => "image/jpeg"]
     *
     * @return array<string,string>
     */
    public function exif(): array;

    /**
     * @return array<string,string>
     */
    public function iptc(): array;
}
