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
use Symfony\Component\Validator\Constraints\All;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Symfony\Validator\PendingImageConstraint;

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
        public ?array $constraints = null,
        public int $errorStatus = 422,
    ) {
    }

    public static function forArgument(ArgumentMetadata $argument): self
    {
        $attributes = $argument->getAttributes(self::class);

        $attribute = null;
        if (!empty($attributes)) {
            $attribute = $attributes[0];
            \assert($attribute instanceof self);
        }

        $constraints = $attribute?->constraints;
        $image = $attribute?->image ?? \is_a(
            $argument->getType() ?? File::class,
            Image::class,
            true
        );

        if (null === $constraints && $image) {
            if ('array' === $argument->getType()) {
                $constraints = [
                    new All([new PendingImageConstraint()]),
                ];
            } else {
                $constraints = [new PendingImageConstraint()];
            }
        }

        return new self(
            path: $attribute?->path ?? $argument->getName(),
            image: $image,
            multiple: $attribute?->multiple ?? !\is_a(
                $argument->getType() ?? File::class,
                File::class,
                true
            ),
            constraints: $constraints,
            errorStatus: $attribute?->errorStatus ?? 422,
        );
    }
}
