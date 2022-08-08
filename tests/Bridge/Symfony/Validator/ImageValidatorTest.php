<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Validator;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Filesystem\Bridge\Symfony\Validator\Constraints\Image;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ImageValidatorTest extends KernelTestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function successful_pending_file(): void
    {
        $violations = self::getContainer()->get(ValidatorInterface::class)->validate(
            new PendingFile(FilesystemTest::FIXTURE_DIR.'/symfony.gif'),
            new Image()
        );

        $this->assertEmpty($violations);
    }

    /**
     * @test
     */
    public function successful_file(): void
    {
        $violations = self::getContainer()->get(ValidatorInterface::class)->validate(
            $this->filesystem()->write('foo.gif', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.gif'))->last(),
            new Image()
        );

        $this->assertEmpty($violations);
    }

    /**
     * @test
     */
    public function failing_pending_file(): void
    {
        $violations = self::getContainer()->get(ValidatorInterface::class)->validate(
            new PendingFile(FilesystemTest::FIXTURE_DIR.'/archive.zip'),
            new Image()
        );

        $this->assertCount(1, $violations);
        $this->assertStringContainsString(
            'This file is not a valid image.',
            (string) $violations
        );
    }

    /**
     * @test
     */
    public function failing_file(): void
    {
        $violations = self::getContainer()->get(ValidatorInterface::class)->validate(
            $this->filesystem()->write('foo.zip', FilesystemTest::FIXTURE_DIR.'/archive.zip')->last(),
            new Image()
        );

        $this->assertCount(1, $violations);
        $this->assertStringContainsString(
            'This file is not a valid image.',
            (string) $violations
        );
    }
}
