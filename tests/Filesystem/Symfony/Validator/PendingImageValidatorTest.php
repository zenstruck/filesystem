<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Validator;

use Symfony\Component\Validator\ConstraintValidator;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Symfony\Validator\PendingImageConstraint;
use Zenstruck\Filesystem\Symfony\Validator\PendingImageValidator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingImageValidatorTest extends PendingFileValidatorTest
{
    public static function validValues(): iterable
    {
        yield [null, new PendingImageConstraint()];
        yield ['', new PendingImageConstraint()];
        yield [new LazyImage('some-path'), new PendingImageConstraint()];
        yield [new PendingImage(fixture('symfony.png')), new PendingImageConstraint()];
    }

    public static function invalidValues(): iterable
    {
        yield [new PendingImage(fixture('archive.zip')), new PendingImageConstraint(), 'This file is not a valid image.'];
        yield [new PendingImage(fixture('symfony.jpg')), new PendingImageConstraint(maxHeight: 10), 'The image height is too big ({{ height }}px). Allowed maximum height is {{ max_height }}px.'];
    }

    public static function exceptionValues(): iterable
    {
        yield ['foo', new PendingImageConstraint()];
        yield [new LazyFile('path'), new PendingImageConstraint()];
        yield [new PendingFile('path'), new PendingImageConstraint()];
    }

    protected function createValidator(): ConstraintValidator
    {
        return new PendingImageValidator();
    }
}
