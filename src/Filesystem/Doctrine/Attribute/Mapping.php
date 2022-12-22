<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine\Attribute;

use Zenstruck\Filesystem\Node\File\Path\Expression;
use Zenstruck\Filesystem\Node\File\Path\Namer;
use Zenstruck\Filesystem\Twig\Template;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @readonly
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Mapping
{
    public ?Namer $namer;

    public function __construct(
        public string $filesystem,

        string|Namer|null $namer = null,

        array $namerContext = [],

        /**
         * Delete the file when object is removed?
         */
        public bool $deleteOnRemove = true,

        /**
         * Delete the old file when updated to a new one?
         */
        public bool $deleteOnUpdate = true,
    ) {
        $this->namer = self::parseNamer($namer, $namerContext);
    }

    /**
     * @internal
     */
    public static function fromArray(array $options): self
    {
        return new self(
            $options['filesystem'] ?? throw new \LogicException('The filesystem key must be set.'),
            $options['namer'] ?? null,
            $options['namerContext'] ?? [],
            $options['deleteOnRemove'] ?? true,
            $options['deleteOnUpdate'] ?? true,
        );
    }

    private static function parseNamer(string|Namer|null $namer, array $context): ?Namer
    {
        if (null === $namer) {
            return null;
        }

        if ($namer instanceof Namer) {
            return $namer->with($context);
        }

        if (2 !== \count($parts = \explode(':', $namer, 2))) {
            return new Namer($namer, $context);
        }

        return match ($parts[0]) {
            'expression' => new Expression($parts[1], $context),
            'twig' => new Template($parts[1], $context),
            default => new Namer($namer, $context),
        };
    }
}
