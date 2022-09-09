<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Bundle\Integration\Doctrine;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;
use Zenstruck\Filesystem\Tests\Fixture\Symfony\Entity\Entity1;
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

        $entity = Entity1Factory::createOne([
            'file' => $this->filesystem()->write('nested/file.png', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last(),
        ]);

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertExists('nested/file.png');

        $entity->remove();

        $this->filesystem()->assertNotExists('nested/file.png');
        $entity->assertNotPersisted();
    }

    /**
     * @test
     */
    public function entity_delete_file_on_update(): void
    {
        $this->filesystem()->assertNotExists('nested/file.png');

        $entity = Entity1Factory::createOne([
            'file' => $this->filesystem()->write('nested/file.png', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last(),
        ]);

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertExists('nested/file.png');

        $entity->file = null;
        $entity->save();

        $this->filesystem()->assertNotExists('nested/file.png');
        $entity->assertPersisted();
    }

    /**
     * @test
     */
    public function can_update_file_on_null_file(): void
    {
        $this->filesystem()->assertNotExists('nested/file.png');

        $entity = Entity1Factory::createOne([
            'file' => null,
        ]);

        $entity->file = $this->filesystem()->write('nested/file.png', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last();
        $entity->save();

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertExists('nested/file.png');
    }

    /**
     * @test
     */
    public function can_update_file_on_existing_file(): void
    {
        $entity = Entity1Factory::createOne([
            'file' => $this->filesystem()->write('nested/file1.png', 'content')->last(),
        ]);

        $entity->file = $this->filesystem()->write('nested/file2.png', new \SplFileInfo(FilesystemTest::FIXTURE_DIR.'/symfony.png'))->last();
        $entity->save();

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
        $entity = Entity1Factory::createOne([
            'file' => null,
        ]);

        $entity->file = new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg', ['namer' => 'slugify']);
        $entity->save();

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertExists('some-crazy-file.png');
        $this->filesystem()->assertExists(Entity1Factory::first()->file->path());
    }

    /**
     * @test
     */
    public function can_update_pending_file_on_existing_file(): void
    {
        $entity = Entity1Factory::createOne([
            'file' => $this->filesystem()->write('nested/file1.png', 'content')->last(),
        ]);

        $this->filesystem()->assertExists('nested/file1.png');

        $entity->file = new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg', ['namer' => 'slugify']);
        $entity->save();

        $this->assertSame('image/png', Entity1Factory::first()->file->mimeType());
        $this->filesystem()->assertNotExists('nested/file1.png');
        $this->filesystem()->assertExists('some-crazy-file.png');
        $this->filesystem()->assertExists(Entity1Factory::first()->file->path());
        $this->filesystem()->assertExists($entity->file);
    }

    /**
     * @test
     */
    public function can_use_callback_for_pending_file_namer(): void
    {
        $entity = Entity1Factory::createOne([
            'title' => 'foo-bar',
            'file' => new PendingFile(FilesystemTest::FIXTURE_DIR.'/symfony.png', ['namer' => function(PendingFile $file, Entity1 $object) {
                return 'baz/'.$file->checksum().'-'.$object->title.'.png';
            }]),
        ]);

        $expected = 'baz/ac6884fc84724d792649552e7211843a-foo-bar.png';

        $this->assertSame($expected, $entity->file->path());
        $this->filesystem()->assertExists($expected);
    }

    /**
     * @test
     */
    public function default_namer(): void
    {
        $entity = Entity1Factory::createOne([
            'file' => new PendingFile(FilesystemTest::FIXTURE_DIR.'/symfony.png'),
        ]);

        $this->filesystem()->assertExists($entity->file->path());
        $this->assertMatchesRegularExpression('#symfony-[0-9a-z]{6}\.png#', $entity->file->path());
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
        yield ['fileChecksum', 'some CRazy file.pNg', 'ac6884fc84724d792649552e7211843a.png'];
        yield ['fileChecksum', 'file no extension', '68aebfb83ffdc6bf16e17a8ebd3b8c35'];

        // custom expression
        yield ['fileExpression', 'some CRazy file.pNg', 'foo/bar/some-crazy-file.png'];
        yield ['fileExpression', 'file no extension', 'foo/bar/file-no-extension'];

        // twig
        yield ['fileTwig', 'some CRazy file.pNg', 'foo/bar/ac6884fc84724d792649552e7211843a-some-crazy-file.png'];
        yield ['fileTwig', 'file no extension', 'foo/bar/68aebfb83ffdc6bf16e17a8ebd3b8c35-file-no-extension'];

        // expression language
        yield ['fileExpressionLanguage', 'some CRazy file.pNg', 'foo/bar/ac6884fc84724d792649552e7211843a-some-crazy-file.png'];
        yield ['fileExpressionLanguage', 'file no extension', 'foo/bar/68aebfb83ffdc6bf16e17a8ebd3b8c35-file-no-extension'];
    }
}
