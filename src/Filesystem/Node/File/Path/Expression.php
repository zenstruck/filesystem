<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Expression extends Namer implements \Stringable
{
    public function __construct(private string $value, array $context = [])
    {
        $context['expression'] = $this;

        parent::__construct('expression', $context);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function slugify(): self
    {
        return new self('{name}{ext}');
    }

    /**
     * @param ?positive-int $length
     */
    public static function checksum(?int $length = null, ?string $algorithm = null): self
    {
        $value = 'checksum';

        if ($length) {
            $value .= ":{$length}";
        }

        if ($algorithm) {
            $value .= ":{$algorithm}";
        }

        return new self("{{$value}}{ext}");
    }
}
