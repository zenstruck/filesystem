<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class IncorrectFileHttpException extends UnprocessableEntityHttpException
{
}
