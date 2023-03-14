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
#[\Attribute(\Attribute::TARGET_PARAMETER|\Attribute::TARGET_PROPERTY)]
class PendingUploadedFile
{
    public function __construct(
        public ?string $path = null,
        public ?array $constraints = null,
        public int $errorStatus = 422,
        public ?bool $image = null,
    ) {
    }

    public static function forArgument(ArgumentMetadata $argument): self
    {
        $attributes = $argument->getAttributes(self::class, ArgumentMetadata::IS_INSTANCEOF);

        if (!empty($attributes)) {
            $attribute = $attributes[0];
            \assert($attribute instanceof self);
        } else {
            $attribute = new self();
        }

        $attribute->path ??= $argument->getName();

        $attribute->image ??= \is_a(
            $argument->getType() ?? File::class,
            Image::class,
            true
        );

        if (null === $attribute->constraints && $attribute->image) {
            if ('array' === $argument->getType()) {
                $attribute->constraints = [
                    new All([new PendingImageConstraint()]),
                ];
            } else {
                $attribute->constraints = [new PendingImageConstraint()];
            }
        }

        return $attribute;
    }
}
