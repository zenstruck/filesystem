<?php

namespace Zenstruck\Filesystem\Tests;

use League\Flysystem\PathTraversalDetected;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\NodeNotFound;
use Zenstruck\Filesystem\Exception\NodeTypeMismatch;
use Zenstruck\Filesystem\Exception\UnableToCopyDirectory;
use Zenstruck\Filesystem\Exception\UnableToMoveDirectory;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\ResourceWrapper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class FilesystemTestCase extends TestCase
{
    protected const TEMP_DIR = __DIR__.'/../var/filesystem';

    /**
     * @before
     */
    public static function cleanup(): void
    {
        (new SymfonyFilesystem())->remove(self::TEMP_DIR);
    }

    /**
     * @test
     */
    public function can_check_if_file_exists(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'file1');

        $this->assertTrue($filesystem->exists('file.txt'));
        $this->assertFalse($filesystem->exists('non-existent'));
    }

    /**
     * @test
     */
    public function cannot_get_non_existent_key(): void
    {
        $this->expectException(NodeNotFound::class);

        $this->createFilesystem()->node('non-existent');
    }

    /**
     * @test
     */
    public function can_get_nodes(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');
        $filesystem->write('subdir/file2.txt', 'file2');
        $filesystem->write('subdir/nested/file3.txt', 'file3');

        $this->assertInstanceOf(File::class, $filesystem->node('/subdir/file1.txt'));
        $this->assertInstanceOf(Directory::class, $filesystem->node('/subdir'));
    }

    /**
     * @test
     */
    public function can_get_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file.txt', 'contents');

        $file = $filesystem->file('/subdir/file.txt');
        $checksum = $file->checksum();

        $this->assertSame('file.txt', $file->name());
        $this->assertSame('txt', $file->extension());
        $this->assertSame('text/plain', $file->mimeType());
        $this->assertSame(8, $file->size()->bytes());
        $this->assertSame((new \DateTime())->format('Y-m-d O'), $file->lastModified()->format('Y-m-d O'));
        $this->assertSame('98bf7d8c15784f0a3d63204441e1e2aa', $checksum->toString());
        $this->assertSame('98bf7d8c15784f0a3d63204441e1e2aa', $checksum->toString());
        $this->assertSame('98bf7d8c15784f0a3d63204441e1e2aa', $checksum->useMd5()->toString());
        $this->assertSame('4a756ca07e9487f482465a99e8286abc86ba4dc7', $checksum->useSha1()->toString());
        $this->assertSame('contents', $file->contents());
        $this->assertSame('contents', \stream_get_contents($file->read()));
        // stream is reset on each call to read
        $this->assertSame('contents', \stream_get_contents($file->read()));
    }

    /**
     * @test
     */
    public function removing_non_existent_key_does_nothing(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'contents');

        $filesystem->delete('non-existent');

        $this->assertTrue($filesystem->exists('file.txt'));
    }

    /**
     * @test
     */
    public function cannot_move_non_existent_source_key(): void
    {
        $this->expectException(NodeNotFound::class);

        $this->createFilesystem()->move('non-existent', 'file.txt');
    }

    /**
     * @test
     */
    public function can_read_file_as_resource(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'contents');

        $this->assertIsResource($resource = $filesystem->file('file.txt')->read());
        $this->assertSame('contents', \stream_get_contents($resource));
    }

    /**
     * @test
     */
    public function cannot_get_outside_of_root(): void
    {
        $this->expectException(PathTraversalDetected::class);

        $this->createFilesystem()->node('../../../some-file.txt');
    }

    /**
     * @test
     */
    public function cannot_copy_from_source_outside_of_root(): void
    {
        $this->expectException(PathTraversalDetected::class);

        $this->createFilesystem()->copy('../../../some-file.txt', 'new-file.txt');
    }

    /**
     * @test
     */
    public function cannot_copy_to_destination_outside_of_root(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'content');

        $this->expectException(PathTraversalDetected::class);

        $filesystem->copy('file.txt', '../../../some-file.txt');
    }

    /**
     * @test
     */
    public function cannot_make_directory_outside_of_root(): void
    {
        $this->expectException(PathTraversalDetected::class);

        $this->createFilesystem()->mkdir('../../../some-dir');
    }

    /**
     * @test
     */
    public function cannot_check_existance_outside_of_root(): void
    {
        $this->expectException(PathTraversalDetected::class);

        $this->createFilesystem()->exists('../../../some-dir');
    }

    /**
     * @test
     */
    public function cannot_remove_file_outside_of_root(): void
    {
        $this->expectException(PathTraversalDetected::class);

        $this->createFilesystem()->delete('../../../some-dir');
    }

    /**
     * @test
     */
    public function cannot_write_outside_of_root(): void
    {
        $this->expectException(PathTraversalDetected::class);

        $this->createFilesystem()->write('../../../some-file.txt', 'contents');
    }

    /**
     * @test
     */
    public function cannot_move_source_outside_of_root(): void
    {
        $this->expectException(PathTraversalDetected::class);

        $this->createFilesystem()->move('../../../some-file.txt', 'new-file.txt');
    }

    /**
     * @test
     */
    public function cannot_move_destination_outside_of_root(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'content');

        $this->expectException(PathTraversalDetected::class);

        $filesystem->move('file.txt', '../../../some-file.txt');
    }

    /**
     * @test
     */
    public function can_copy_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file1.txt', 'contents');

        $this->assertFalse($filesystem->exists('file2.txt'));

        $filesystem->copy('/file1.txt', 'file2.txt');

        $this->assertTrue($filesystem->exists('file2.txt'));
        $this->assertSame('contents', $filesystem->file('file2.txt')->contents());
    }

    /**
     * @test
     */
    public function can_copy_file_over_existing_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file1.txt', 'file1');
        $filesystem->write('file2.txt', 'file2');

        $this->assertSame('file1', $filesystem->file('file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('file2.txt')->contents());

        $filesystem->copy('/file1.txt', 'file2.txt');

        $this->assertSame('file1', $filesystem->file('file1.txt')->contents());
        $this->assertSame('file1', $filesystem->file('file2.txt')->contents());
    }

    /**
     * @test
     */
    public function cannot_copy_file_to_existing_dir(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'file1');
        $filesystem->mkdir('dir');

        try {
            $filesystem->copy('file.txt', 'dir');
        } catch (UnableToCopyFile) {
            $this->assertInstanceOf(Directory::class, $filesystem->node('dir'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function cannot_copy_non_existent_source_key(): void
    {
        $this->expectException(NodeNotFound::class);

        $this->createFilesystem()->copy('non-existent', 'file.txt');
    }

    /**
     * @test
     */
    public function can_make_directory(): void
    {
        $filesystem = $this->createFilesystem();

        $this->assertFalse($filesystem->exists('subdir'));

        $filesystem->mkdir('/subdir');

        $this->assertTrue($filesystem->exists('subdir'));
        $this->assertInstanceOf(Directory::class, $filesystem->node('subdir'));
    }

    /**
     * @test
     */
    public function can_make_nested_directory(): void
    {
        $filesystem = $this->createFilesystem();

        $this->assertFalse($filesystem->exists('subdir'));
        $this->assertFalse($filesystem->exists('subdir/nested'));

        $filesystem->mkdir('/subdir/nested');

        $this->assertTrue($filesystem->exists('subdir'));
        $this->assertInstanceOf(Directory::class, $filesystem->node('subdir'));
        $this->assertTrue($filesystem->exists('subdir/nested'));
        $this->assertInstanceOf(Directory::class, $filesystem->node('subdir/nested'));
    }

    /**
     * @test
     */
    public function making_directory_that_already_exists_keeps_files(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');
        $filesystem->write('subdir/file2.txt', 'file2');

        $this->assertSame('file1', $filesystem->file('subdir/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir/file2.txt')->contents());

        $filesystem->mkdir('subdir');

        $this->assertSame('file1', $filesystem->file('subdir/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir/file2.txt')->contents());
    }

    /**
     * @test
     */
    public function cannot_make_directory_if_file_exists(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file', 'contents');

        try {
            $filesystem->mkdir('file');
        } catch (UnableToCreateDirectory) {
            $this->assertSame('contents', $filesystem->file('file')->contents());

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function can_remove_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file.txt', 'file1');

        $this->assertTrue($filesystem->exists('/subdir/file.txt'));
        $this->assertTrue($filesystem->exists('/subdir'));

        $filesystem->delete('subdir');

        $this->assertFalse($filesystem->exists('/subdir/file.txt'));
        $this->assertFalse($filesystem->exists('/subdir'));
    }

    /**
     * @test
     */
    public function can_remove_empty_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->mkdir('subdir');

        $this->assertTrue($filesystem->exists('/subdir'));

        $filesystem->delete('subdir');

        $this->assertFalse($filesystem->exists('/subdir'));
    }

    /**
     * @test
     */
    public function can_delete_root(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'contents');

        $this->assertTrue($filesystem->exists());
        $this->assertTrue($filesystem->exists('file.txt'));

        $filesystem->delete(); // delete root

        $this->assertFalse($filesystem->exists('file.txt'));
        $this->assertFalse($filesystem->exists());
    }

    /**
     * @test
     */
    public function can_remove_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file.txt', 'file1');

        $this->assertTrue($filesystem->exists('/subdir/file.txt'));

        $filesystem->delete('subdir/file.txt');

        $this->assertFalse($filesystem->exists('/subdir/file.txt'));
    }

    /**
     * @test
     */
    public function can_move_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'contents');

        $this->assertTrue($filesystem->exists('file.txt'));
        $this->assertSame('contents', $filesystem->file('file.txt')->contents());

        $filesystem->move('file.txt', 'new-file.txt');

        $this->assertFalse($filesystem->exists('file.txt'));
        $this->assertTrue($filesystem->exists('new-file.txt'));
        $this->assertSame('contents', $filesystem->file('new-file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_move_file_over_existing_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file1.txt', 'file1');
        $filesystem->write('file2.txt', 'file2');

        $this->assertSame('file2', $filesystem->file('file2.txt')->contents());

        $filesystem->move('file1.txt', 'file2.txt');

        $this->assertSame('file1', $filesystem->file('file2.txt')->contents());
    }

    /**
     * @test
     */
    public function cannot_move_file_to_existing_dir(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'file1');
        $filesystem->mkdir('dir');

        try {
            $filesystem->move('file.txt', 'dir');
        } catch (UnableToMoveFile) {
            $this->assertInstanceOf(Directory::class, $filesystem->node('dir'));
            $this->assertInstanceOf(File::class, $filesystem->node('file.txt'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function can_check_if_directory_exists(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->mkdir('subdir');

        $this->assertTrue($filesystem->exists('subdir'));
        $this->assertFalse($filesystem->exists('non-existent'));
    }

    /**
     * @test
     */
    public function can_get_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');
        $filesystem->write('subdir/file2.txt', 'file2');
        $filesystem->write('subdir/nested/file3.txt', 'file3');

        $dir = $filesystem->directory('/subdir');

        $this->assertCount(3, $dir);

        /** @var Node[] $listing */
        $listing = \iterator_to_array($dir);
        \usort($listing, static fn(Node $a, Node $b) => \strcmp($a->path(), $b->path()));

        $this->assertCount(3, $listing);
        $this->assertInstanceOf(File::class, $listing[0]);

        $this->assertInstanceOf(File::class, $listing[1]);
        $this->assertSame('file2', $listing[1]->contents());
        $this->assertIsResource($listing[1]->read());

        $this->assertInstanceOf(Directory::class, $listing[2]);

        $this->assertCount(2, $dir->files());
        $this->assertCount(1, $dir->directories());
    }

    /**
     * @test
     */
    public function can_get_empty_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->mkdir('foo/bar');

        $this->assertCount(0, $filesystem->directory('foo/bar'));
    }

    /**
     * @test
     */
    public function cannot_get_directory_for_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');

        $this->expectException(NodeTypeMismatch::class);

        $filesystem->directory('subdir/file1.txt');
    }

    /**
     * @test
     */
    public function cannot_get_file_for_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');

        $this->expectException(NodeTypeMismatch::class);

        $filesystem->file('subdir');
    }

    /**
     * @test
     */
    public function can_get_directory_recursive(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');
        $filesystem->write('subdir/file2.txt', 'file2');
        $filesystem->write('subdir/nested/file3.txt', 'file3');
        $filesystem->write('subdir/nested/nested1/file4.txt', 'file4');
        $filesystem->mkdir('subdir/sub');
        $filesystem->mkdir('subdir/sub/sub2');
        $filesystem->mkdir('subdir/sub/sub2/sub3');

        $dir = $filesystem->directory('subdir');

        $this->assertCount(9, \iterator_to_array($dir->recursive()));
        $this->assertCount(4, \iterator_to_array($dir->recursive()->files()));
        $this->assertCount(5, \iterator_to_array($dir->recursive()->directories()));
    }

    /**
     * @test
     */
    public function can_get_root_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');
        $filesystem->write('subdir/file2.txt', 'file2');
        $filesystem->write('subdir/nested/file3.txt', 'file3');

        $root = $filesystem->directory();

        $this->assertCount(1, $root);
    }

    /**
     * @test
     */
    public function can_check_if_root_exists(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');

        $this->assertTrue($filesystem->exists());
    }

    /**
     * @test
     */
    public function can_write_string_contents(): void
    {
        $filesystem = $this->createFilesystem();

        $filesystem->write('file.txt', 'contents');

        $this->assertSame('contents', $filesystem->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_write_string_contents_to_existing_file(): void
    {
        $filesystem = $this->createFilesystem();

        $filesystem->write('file.txt', 'contents');
        $filesystem->write('file.txt', 'contents2');

        $this->assertSame('contents2', $filesystem->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function cannot_write_string_to_existing_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->mkdir('dir');

        try {
            $filesystem->write('dir', 'contents');
        } catch (UnableToWriteFile) {
            $this->assertInstanceOf(Directory::class, $filesystem->node('dir'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function can_write_file(): void
    {
        $filesystem = $this->createFilesystem();

        $filesystem->write('file.txt', __FILE__);

        $this->assertStringContainsString('<?php', $filesystem->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_write_file_to_existing_file(): void
    {
        $filesystem = $this->createFilesystem();

        $filesystem->write('file.txt', 'contents');

        $this->assertStringNotContainsString('<?php', $filesystem->file('file.txt')->contents());

        $filesystem->write('file.txt', __FILE__);

        $this->assertStringContainsString('<?php', $filesystem->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function cannot_write_file_to_existing_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->mkdir('dir');

        try {
            $filesystem->write('dir', __FILE__);
        } catch (UnableToWriteFile) {
            $this->assertInstanceOf(Directory::class, $filesystem->node('dir'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function can_write_resource(): void
    {
        $filesystem = $this->createFilesystem();

        $filesystem->write('file.txt', ResourceWrapper::wrap('contents')->get());

        $this->assertSame('contents', $filesystem->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_write_resource_to_existing_file(): void
    {
        $filesystem = $this->createFilesystem();

        $filesystem->write('file.txt', 'contents');
        $filesystem->write('file.txt', ResourceWrapper::wrap('contents2')->get());

        $this->assertSame('contents2', $filesystem->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function cannot_write_resource_to_existing_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->mkdir('dir');

        try {
            $filesystem->write('dir', ResourceWrapper::wrap('contents')->get());
        } catch (UnableToWriteFile) {
            $this->assertInstanceOf(Directory::class, $filesystem->node('dir'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function can_write_filesystem_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file2.txt', 'contents');
        $filesystem->write('file.txt', $filesystem->file('file2.txt'));

        $this->assertSame('contents', $filesystem->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function can_write_filesystem_file_to_existing_file(): void
    {
        $filesystem = $this->createFilesystem();

        $filesystem->write('file.txt', 'contents');
        $filesystem->write('file2.txt', 'contents2');
        $filesystem->write('file.txt', $filesystem->file('file2.txt'));

        $this->assertSame('contents2', $filesystem->file('file.txt')->contents());
    }

    /**
     * @test
     */
    public function cannot_write_filesystem_file_to_existing_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->mkdir('dir');
        $filesystem->write('file.txt', 'contents');

        try {
            $filesystem->write('dir', $filesystem->file('file.txt'));
        } catch (UnableToWriteFile) {
            $this->assertInstanceOf(Directory::class, $filesystem->node('dir'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function cannot_copy_dir_to_existing_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'file1');
        $filesystem->mkdir('dir');

        $this->expectException(UnableToCopyDirectory::class);

        $filesystem->copy('dir', 'file.txt');
    }

    /**
     * @test
     */
    public function can_copy_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir1/file1.txt', 'file1');
        $filesystem->write('subdir1/nested/file2.txt', 'file2');

        $this->assertTrue($filesystem->exists('subdir1'));
        $this->assertFalse($filesystem->exists('subdir2'));
        $this->assertSame('file1', $filesystem->file('subdir1/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir1/nested/file2.txt')->contents());

        $filesystem->copy('subdir1', 'subdir2');

        $this->assertTrue($filesystem->exists('subdir1'));
        $this->assertTrue($filesystem->exists('subdir2'));
        $this->assertSame('file1', $filesystem->file('subdir1/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir1/nested/file2.txt')->contents());
        $this->assertSame('file1', $filesystem->file('subdir2/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir2/nested/file2.txt')->contents());
    }

    /**
     * @test
     */
    public function can_copy_directory_over_existing_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir1/file1.txt', 'file1');
        $filesystem->write('subdir1/file2.txt', 'file2');
        $filesystem->write('subdir2/file3.txt', 'file3');

        $this->assertSame('file1', $filesystem->file('subdir1/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir1/file2.txt')->contents());
        $this->assertTrue($filesystem->exists('subdir2/file3.txt'));

        $filesystem->copy('subdir1', 'subdir2');

        $this->assertTrue($filesystem->exists('subdir1'));
        $this->assertTrue($filesystem->exists('subdir2'));
        $this->assertSame('file1', $filesystem->file('subdir1/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir1/file2.txt')->contents());
        $this->assertSame('file1', $filesystem->file('subdir2/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir2/file2.txt')->contents());
        $this->assertFalse($filesystem->exists('subdir2/file3.txt'));
    }

    /**
     * @test
     */
    public function cannot_move_dir_to_existing_file(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('file.txt', 'file1');
        $filesystem->mkdir('dir');

        try {
            $filesystem->move('dir', 'file.txt');
        } catch (UnableToMoveDirectory) {
            $this->assertTrue($filesystem->exists('dir'));
            $this->assertTrue($filesystem->exists('file.txt'));

            return;
        }

        $this->fail('Exception not thrown.');
    }

    /**
     * @test
     */
    public function can_move_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir/file1.txt', 'file1');
        $filesystem->write('subdir/file2.txt', 'file2');

        $this->assertTrue($filesystem->exists('subdir'));
        $this->assertSame('file1', $filesystem->file('subdir/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir/file2.txt')->contents());

        $filesystem->move('subdir', 'new-subdir');

        $this->assertFalse($filesystem->exists('subdir'));
        $this->assertTrue($filesystem->exists('new-subdir'));
        $this->assertSame('file1', $filesystem->file('new-subdir/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('new-subdir/file2.txt')->contents());
    }

    /**
     * @test
     */
    public function can_move_directory_over_existing_directory(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('subdir1/file1.txt', 'file1');
        $filesystem->write('subdir1/file2.txt', 'file2');
        $filesystem->write('subdir2/file3.txt', 'file3');

        $this->assertSame('file1', $filesystem->file('subdir1/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir1/file2.txt')->contents());
        $this->assertTrue($filesystem->exists('subdir2/file3.txt'));

        $filesystem->move('subdir1', 'subdir2');

        $this->assertFalse($filesystem->exists('subdir1'));
        $this->assertTrue($filesystem->exists('subdir2'));
        $this->assertSame('file1', $filesystem->file('subdir2/file1.txt')->contents());
        $this->assertSame('file2', $filesystem->file('subdir2/file2.txt')->contents());
        $this->assertFalse($filesystem->exists('subdir2/file3.txt'));
    }

    /**
     * @test
     */
    public function is_fluent(): void
    {
        $filesystem = $this->createFilesystem();

        $this->assertSame($filesystem, $filesystem->delete('foo'));
        $this->assertSame($filesystem, $filesystem->mkdir('foo'));
        $this->assertSame($filesystem, $filesystem->write('foo/bar.txt', 'content'));
        $this->assertSame($filesystem, $filesystem->copy('foo/bar.txt', 'foo/baz.txt'));
        $this->assertSame($filesystem, $filesystem->copy('foo', 'bar'));
        $this->assertSame($filesystem, $filesystem->move('foo/bar.txt', 'baz/baz.txt'));
        $this->assertSame($filesystem, $filesystem->move('foo', 'baz'));
        $this->assertSame($filesystem, $filesystem->chmod('baz/baz.txt', 'public'));
    }

    /**
     * @test
     */
    public function can_get_last_modified_node(): void
    {
        $filesystem = $this->createFilesystem();

        $this->assertSame('foo', $filesystem->mkdir('foo')->last()->path());
        $this->assertSame('foo/bar.txt', $filesystem->write('foo/bar.txt', 'content')->last()->path());
        $this->assertSame('foo/baz.txt', $filesystem->copy('foo/bar.txt', 'foo/baz.txt')->last()->path());
        $this->assertSame('bar', $filesystem->copy('foo', 'bar')->last()->path());
        $this->assertSame('baz/baz.txt', $filesystem->move('foo/bar.txt', 'baz/baz.txt')->last()->path());
        $this->assertSame('baz', $filesystem->move('foo', 'baz')->last()->path());
        $this->assertSame('baz/baz.txt', $filesystem->chmod('baz/baz.txt', 'public')->last()->path());
    }

    /**
     * @test
     */
    public function cannot_get_last_modified_node_if_no_operations_performed(): void
    {
        $filesystem = $this->createFilesystem();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No operations have been performed.');

        $filesystem->last();
    }

    /**
     * @test
     */
    public function cannot_get_last_modified_node_after_delete(): void
    {
        $filesystem = $this->createFilesystem()->write('foo.txt', 'bar')->delete('foo.txt');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Last node not available as the last operation deleted it.');

        $filesystem->last();
    }

    abstract protected function createFilesystem(): Filesystem;
}
