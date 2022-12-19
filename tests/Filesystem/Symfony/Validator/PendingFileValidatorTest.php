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
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Symfony\Validator\PendingFileConstraint;
use Zenstruck\Filesystem\Symfony\Validator\PendingFileValidator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PendingFileValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function validation_pass($value, $constraint): void
    {
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public static function validValues(): iterable
    {
        yield [null, new PendingFileConstraint(mimeTypes: ['image/png'])];
        yield ['', new PendingFileConstraint(mimeTypes: ['image/png'])];
        yield [new LazyFile('some-path'), new PendingFileConstraint(mimeTypes: ['image/png'])];
        yield [new LazyImage('some-path'), new PendingFileConstraint(mimeTypes: ['image/png'])];
        yield [new PendingFile(fixture('symfony.png')), new PendingFileConstraint(mimeTypes: ['image/png'])];
        yield [new PendingImage(fixture('symfony.png')), new PendingFileConstraint(mimeTypes: ['image/png'])];
    }

    /**
     * @test
     * @dataProvider invalidValues
     */
    public function validation_fail($value, $constraint, $message): void
    {
        $this->validator->validate($value, $constraint);

        $this->assertCount(1, $this->context->getViolations());
        $this->assertSame($message, $this->context->getViolations()->get(0)->getMessage());
    }

    public static function invalidValues(): iterable
    {
        yield [new PendingFile(fixture('symfony.jpg')), new PendingFileConstraint(mimeTypes: ['image/png']), 'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.'];
        yield [new PendingImage(fixture('symfony.jpg')), new PendingFileConstraint(mimeTypes: ['image/png']), 'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.'];
    }

    /**
     * @test
     * @dataProvider exceptionValues
     */
    public function validation_exception($value, $constaint): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($value, $constaint);
    }

    public static function exceptionValues(): iterable
    {
        yield ['foo', new PendingFileConstraint()];
    }

    protected function createValidator(): ConstraintValidator
    {
        return new PendingFileValidator();
    }
}
