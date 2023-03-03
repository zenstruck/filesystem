<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @internal
 */
if (\interface_exists(ValueResolverInterface::class)) {
    class PendingFileValueResolver implements ValueResolverInterface
    {
        use PendingFileValueResolverTrait {
            resolve as resolveArgument;
        }

        /**
         * @return iterable<PendingFile|array|null>
         */
        public function resolve(Request $request, ArgumentMetadata $argument): iterable
        {
            if (!RequestFilesExtractor::supports($argument)) {
                return [];
            }

            return $this->resolveArgument($request, $argument);
        }
    }
} else {
    class PendingFileValueResolver implements ArgumentValueResolverInterface
    {
        use PendingFileValueResolverTrait;

        public function supports(Request $request, ArgumentMetadata $argument): bool
        {
            return RequestFilesExtractor::supports($argument);
        }
    }
}
