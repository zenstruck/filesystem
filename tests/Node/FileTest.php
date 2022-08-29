<?php

namespace Zenstruck\Filesystem\Tests\Node;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;
use Zenstruck\Filesystem\Util;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileTest extends TestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     * @dataProvider extensionProvider
     */
    public function can_parse_extension_and_name_without_extension(string $filename, ?string $extension, string $nameWithoutExtension): void
    {
        $file = $this->filesystem()->write($filename, 'content')->last()->ensureFile();

        $this->assertSame($extension, $file->extension());
        $this->assertSame($nameWithoutExtension, $file->nameWithoutExtension());

        Util::fs()->dumpFile($file = FilesystemTest::TEMP_DIR.'/'.$filename, 'content');

        $pending = new PendingFile($file);

        $this->assertSame($extension, $pending->originalExtension());
        $this->assertSame($nameWithoutExtension, $pending->originalNameWithoutExtension());
        $this->assertSame($extension, $pending->extension());
        $this->assertSame($nameWithoutExtension, $pending->nameWithoutExtension());

        $pending = new PendingFile(new UploadedFile($file, $filename));

        $this->assertSame($extension, $pending->originalExtension());
        $this->assertSame($nameWithoutExtension, $pending->originalNameWithoutExtension());
        $this->assertSame($extension, $pending->extension());
        $this->assertSame($nameWithoutExtension, $pending->nameWithoutExtension());
    }

    public static function extensionProvider(): iterable
    {
        yield ['foo', null, 'foo'];
        yield ['nested/foo', null, 'foo'];
        yield ['foo.txt', 'txt', 'foo'];
        yield ['nested/foo.txt', 'txt', 'foo'];
        yield ['foo.tar', 'tar', 'foo'];
        yield ['foo.tar.gz', 'tar.gz', 'foo'];
        yield ['foo.tar.bz2', 'tar.bz2', 'foo'];
        yield ['foo.gz', 'gz', 'foo'];
        yield ['foo.bz2', 'bz2', 'foo'];
    }

    /**
     * @test
     */
    public function can_guess_extension(): void
    {
        $file1 = $this->filesystem()->write('foo.jpg', 'content')->last();
        $file2 = $this->filesystem()->write('foo', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last();

        $this->assertSame('jpg', $file1->guessExtension());
        $this->assertSame('png', $file2->guessExtension());
    }
}
