<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Zenstruck\Filesystem\Attribute\PendingUploadedFile;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Symfony\Validator\PendingFileConstraint;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class ValidatedArgumentResolverController
{
    #[Route('/validated-file', name: 'validated-file')]
    public function validatedFile(
        #[PendingUploadedFile(
            constraints: [new PendingFileConstraint(mimeTypes: ['application/pdf'])],
            errorStatus: 500
        )]
        File $file
    ): Response {
        return new Response($file->contents());
    }
}
