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

use Symfony\Component\Validator\Constraint;
use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Node\Path\Namer;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @readonly
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
final class UploadedFile extends PendingUploadedFile
{
    public string|Namer $namer;

    /**
     * @param Constraint[]|null $constraints
     */
    public function __construct(
        public string $filesystem,
        string|Namer|null $namer = null,
        ?string $path = null,
        ?array $constraints = null,
        ?bool $image = null,
    ) {
        parent::__construct($path, $constraints, $image);

        $this->namer = $namer ?? new Expression('{checksum}/{name}{ext}');
    }
}
