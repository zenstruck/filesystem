<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Bundle\Integration\Doctrine;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;
use Zenstruck\Filesystem\Tests\Fixture\Symfony\Factory\Entity1Factory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileTypeTest extends KernelTestCase
{
    use Factories, InteractsWithFilesystem, ResetDatabase;

    /**
     * @test
     */
    public function entity_save_load_delete(): void
    {
        $this->filesystem()->assertNotExists('nested/file.png');

        $post = Entity1Factory::createOne([
            'file' => $this->filesystem()->write('nested/file.png', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last(),
        ]);

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertExists('nested/file.png');

        $post->remove();

        $this->filesystem()->assertNotExists('nested/file.png');
        $post->assertNotPersisted();
    }

    /**
     * @test
     */
    public function entity_delete_file_on_update(): void
    {
        $this->filesystem()->assertNotExists('nested/file.png');

        $post = Entity1Factory::createOne([
            'file' => $this->filesystem()->write('nested/file.png', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last(),
        ]);

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertExists('nested/file.png');

        $post->file = null;
        $post->save();

        $this->filesystem()->assertNotExists('nested/file.png');
        $post->assertPersisted();
    }

    /**
     * @test
     */
    public function can_update_file_on_null_file(): void
    {
        $this->filesystem()->assertNotExists('nested/file.png');

        $post = Entity1Factory::createOne([
            'file' => null,
        ]);

        $post->file = $this->filesystem()->write('nested/file.png', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last();
        $post->save();

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertExists('nested/file.png');
    }

    /**
     * @test
     */
    public function can_update_file_on_existing_file(): void
    {
        $post = Entity1Factory::createOne([
            'file' => $this->filesystem()->write('nested/file1.png', 'content')->last(),
        ]);

        $post->file = $this->filesystem()->write('nested/file2.png', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last();
        $post->save();

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertNotExists('nested/file1.png');
        $this->filesystem()->assertExists('nested/file2.png');
    }

    /**
     * @test
     */
    public function can_create_with_pending_file(): void
    {
        $this->filesystem()->assertNotExists('composer.json');

        $entity = Entity1Factory::createOne([
            'file' => new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg', ['namer' => 'slugify']),
        ]);

        $this->filesystem()->assertExists($entity->file->path());
        $this->filesystem()->assertExists('some-crazy-file.png');
        $this->assertSame('image/png', $entity->file->mimeType());
    }

    /**
     * @test
     */
    public function can_update_pending_file_on_null_file(): void
    {
        $post = Entity1Factory::createOne([
            'file' => null,
        ]);

        $post->file = new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg', ['namer' => 'slugify']);
        $post->save();

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertExists('some-crazy-file.png');
        $this->filesystem()->assertExists(Entity1Factory::first()->file->path());
    }

    /**
     * @test
     */
    public function can_update_pending_file_on_existing_file(): void
    {
        $post = Entity1Factory::createOne([
            'file' => $this->filesystem()->write('nested/file1.png', 'content')->last(),
        ]);

        $this->filesystem()->assertExists('nested/file1.png');

        $post->file = new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg', ['namer' => 'slugify']);
        $post->save();

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertNotExists('nested/file1.png');
        $this->filesystem()->assertExists('some-crazy-file.png');
        $this->filesystem()->assertExists(Entity1Factory::first()->file->path());
    }

    /**
     * @test
     */
    public function can_use_callback_for_pending_file_namer(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function default_namer(): void
    {
        $this->markTestIncomplete();

        $entity = Entity1Factory::createOne([
            'file' => new PendingFile(FilesystemTest::FIXTURE_DIR.'/symfony.png'),
        ]);

        $this->filesystem()->assertExists($entity->file->path());
        $this->assertMatchesRegularExpression('#symfony-1-[0-9a-z]{6}\.png#', $entity->file->path());
    }

    /**
     * @test
     * @dataProvider namerProvider
     */
    public function namers($property, $filename, $expected): void
    {
        $this->filesystem()->assertNotExists($expected);

        Entity1Factory::createOne([
            $property => new PendingFile(FilesystemTest::FIXTURE_DIR.'/'.$filename),
        ]);

        $this->filesystem()->assertExists($expected);
        $this->filesystem()->assertExists(Entity1Factory::first()->{$property}->path());
    }

    public static function namerProvider(): iterable
    {
        // slugify
        yield ['fileSlugify', 'some CRazy file.pNg', 'some-crazy-file.png'];
        yield ['fileSlugify', 'file no extension', 'file-no-extension'];

        // checksum
        yield ['fileChecksum', 'some CRazy file.pNg', 'f75b8179e4bbe7e2b4a074dcef62de95.png'];
        yield ['fileChecksum', 'file no extension', '68aebfb83ffdc6bf16e17a8ebd3b8c35'];

        // custom expression
        yield ['fileExpression', 'some CRazy file.pNg', 'foo/bar/some-crazy-file.png'];
        yield ['fileExpression', 'file no extension', 'foo/bar/file-no-extension'];

        // twig
        yield ['fileTwig', 'some CRazy file.pNg', 'foo/bar/f75b8179e4bbe7e2b4a074dcef62de95-some-crazy-file.png'];
        yield ['fileTwig', 'file no extension', 'foo/bar/68aebfb83ffdc6bf16e17a8ebd3b8c35-file-no-extension'];

        // expression language
        yield ['fileExpressionLanguage', 'some CRazy file.pNg', 'foo/bar/f75b8179e4bbe7e2b4a074dcef62de95-some-crazy-file.png'];
        yield ['fileExpressionLanguage', 'file no extension', 'foo/bar/68aebfb83ffdc6bf16e17a8ebd3b8c35-file-no-extension'];
    }
}
