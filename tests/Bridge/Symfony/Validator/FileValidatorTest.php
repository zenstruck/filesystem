<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Validator;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Filesystem\Bridge\Symfony\Validator\Constraints\File;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileValidatorTest extends KernelTestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function successful_pending_file(): void
    {
        $violations = self::getContainer()->get(ValidatorInterface::class)->validate(
            new PendingFile(FilesystemTest::FIXTURE_DIR.'/symfony.gif'),
            new File(maxSize: '1m', mimeTypes: 'image/gif')
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
            new File(maxSize: '1m', mimeTypes: 'image/gif')
        );

        $this->assertEmpty($violations);
    }

    /**
     * @test
     */
    public function failing_pending_file(): void
    {
        $violations = self::getContainer()->get(ValidatorInterface::class)->validate(
            new PendingFile(FilesystemTest::FIXTURE_DIR.'/symfony.gif'),
            new File(mimeTypes: 'text/plain')
        );

        $this->assertCount(1, $violations);
        $this->assertStringContainsString(
            'The mime type of the file is invalid ("image/gif"). Allowed mime types are "text/plain".',
            (string) $violations
        );
    }

    /**
     * @test
     */
    public function failing_file(): void
    {
        $violations = self::getContainer()->get(ValidatorInterface::class)->validate(
            $this->filesystem()->write('foo.gif', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.gif'))->last(),
            new File(mimeTypes: 'text/plain')
        );

        $this->assertCount(1, $violations);
        $this->assertStringContainsString(
            'The mime type of the file is invalid ("image/gif"). Allowed mime types are "text/plain".',
            (string) $violations
        );
    }
}
