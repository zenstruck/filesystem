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
use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Node\Path\Namer;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class UploadedFile extends PendingUploadedFile
{
    public string|Namer $namer;

    public function __construct(
        public string $filesystem,
        string|Namer|null $namer = null,
        ?string $path = null,
        ?array $constraints = null,
        int $errorStatus = 422,
        ?bool $image = null,
    ) {
        parent::__construct($path, $constraints, $errorStatus, $image);

        $this->namer = $namer ?? Expression::uniqueSlug();
    }
}
