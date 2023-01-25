<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine;

use Zenstruck\Filesystem\Node\File\Path\Expression;
use Zenstruck\Filesystem\Node\File\Path\Namer;
use Zenstruck\Filesystem\Twig\Template;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Mapping
{
    private ?Namer $namer;

    public function __construct(
        private ?string $filesystem = null,
        string|Namer|null $namer = null,
        array $namerContext = [],
    ) {
        $this->namer = self::parseNamer($namer, $namerContext);
    }

    public function filesystem(): ?string
    {
        return $this->filesystem;
    }

    public function namer(): ?Namer
    {
        return $this->namer;
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
