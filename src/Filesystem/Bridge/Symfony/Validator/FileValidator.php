<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\FileValidator as BaseFileValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Zenstruck\Filesystem\Bridge\Symfony\Validator\Constraints\File;
use Zenstruck\Filesystem\Node\File as FileNode;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Util\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FileValidator extends BaseFileValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof File) {
            throw new UnexpectedTypeException($constraint, File::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$value instanceof FileNode) {
            throw new UnexpectedValueException($value, FileNode::class);
        }

        if ($value instanceof PendingFile) {
            parent::validate($value->localFile(), $constraint);

            return;
        }

        parent::validate(TempFile::for($value), $constraint);
    }
}
