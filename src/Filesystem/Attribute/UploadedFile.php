<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Attribute;

use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class UploadedFile
{
    public function __construct(
        public ?string $path = null,
        public ?bool $image = null,
        public ?bool $multiple = null,
    ) {
    }

    public static function forArgument(ArgumentMetadata $argument): self
    {
        $attributes = $argument->getAttributes(self::class);

        $attribute = null;
        if (!empty($attributes)) {
            $attribute = $attributes[0];
            assert($attribute instanceof self);
        }

        return new self(
            path: $attribute->path ?? $argument->getName(),
            image: $attribute->image ?? \is_a(
                $argument->getType() ?? File::class,
                Image::class,
                true
            ),
            multiple: $attribute->multiple ?? !\is_a(
                $argument->getType() ?? File::class,
                File::class,
                true
            ),
        );
    }
}
