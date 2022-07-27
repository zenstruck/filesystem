<?php

namespace Zenstruck\Filesystem\Tests\Test;

use Zenstruck\Dimension\Information;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Node\File\Checksum;
use Zenstruck\Filesystem\Test\FixtureFilesystemProvider;
use Zenstruck\Filesystem\Test\Node\TestDirectory;
use Zenstruck\Filesystem\Test\Node\TestFile;
use Zenstruck\Filesystem\Test\Node\TestImage;
use Zenstruck\Filesystem\Test\TestFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestFilesystemTest extends FilesystemTest implements FixtureFilesystemProvider
{
    /**
     * @test
     */
    public function can_make_assertions(): void
    {
        $filesystem = $this->filesystem();
        $filesystem
            ->mkdir('foo')
            ->write('file1.txt', 'contents1')
            ->write('nested/file2.txt', 'contents1')
            ->copy('fixture://symfony.png', 'symfony.png')
        ;

        $filesystem
            ->assertExists('foo')
            ->assertNotExists('invalid')
            ->assertFileExists('file1.txt')
            ->assertDirectoryExists('foo')
            ->assertImageExists('symfony.png')
            ->assertSame('symfony.png', 'fixture://symfony.png')
            ->assertNotSame('file1.txt', 'fixture://symfony.png')
            ->assertDirectoryExists('', function(TestDirectory $dir) {
                $dir
                    ->assertCount(4)
                    ->files()->assertCount(2)
                ;

                $dir
                    ->recursive()
                    ->assertCount(5)
                    ->files()->assertCount(3)
                ;
            })
            ->assertFileExists('file1.txt', function(TestFile $file) {
                $file
                    ->assertVisibilityIs('public')
                    ->assertChecksum($file->checksum()->toString())
                    ->assertChecksum(function(Checksum $actual) use ($file) {
                        $this->assertSame($file->checksum()->useSha1()->toString(), $actual->useSha1()->toString());
                    })
                    ->assertContentIs('contents1')
                    ->assertContentIsNot('foo')
                    ->assertContentContains('1')
                    ->assertContentDoesNotContain('foo')
                    ->assertMimeTypeIs('text/plain')
                    ->assertMimeTypeIsNot('foo')
                    ->assertLastModified(function(\DateTimeInterface $actual) {
                        $this->assertTrue($actual->getTimestamp() > 0);
                    })
                    ->assertSize(9)
                    ->assertSize(function(Information $actual) {
                        $this->assertTrue($actual->isSmallerThan('1mb'));
                    })
                ;
            })
            ->assertImageExists('symfony.png', function(TestImage $image) {
                $image
                    ->assertHeight(678)
                    ->assertWidth(563)
                    ->assertHeight(function($actual) {
                        $this->assertGreaterThan(600, $actual);
                        $this->assertLessThan(700, $actual);
                    })
                    ->assertWidth(function($actual) {
                        $this->assertGreaterThan(500, $actual);
                        $this->assertLessThan(600, $actual);
                    })
                ;
            })
        ;
    }

    /**
     * @test
     */
    public function can_access_fixture(): void
    {
        $this->assertTrue($this->filesystem()->has('fixture://symfony.png'));
    }

    public function fixtureFilesystem(): string|Filesystem
    {
        return self::FIXTURE_DIR;
    }

    protected function createFilesystem(): Filesystem
    {
        return new TestFilesystem(new AdapterFilesystem(self::TEMP_DIR));
    }
}
