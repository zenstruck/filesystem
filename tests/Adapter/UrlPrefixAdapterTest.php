<?php

namespace Zenstruck\Filesystem\Tests\Adapter;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\LocalAdapter;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UrlPrefixAdapterTest extends FilesystemTest
{
    /**
     * @test
     */
    public function can_access_node_url(): void
    {
        $filesystem = $this->createFilesystem();
        $filesystem->write('nested/file.txt', 'contents');

        $this->assertSame('https://example.com/sub/nested/file.txt', $filesystem->file('nested/file.txt')->url()->toString());
        $this->assertSame('https://example.com/sub/nested/file.txt', $filesystem->file('/nested/file.txt')->url()->toString());
        $this->assertSame('https://example.com/sub/nested/file.txt', $filesystem->file('nested/file.txt')->url()->toString());
    }

    /**
     * @test
     */
    public function can_use_multiple_prefixes_to_provide_a_deterministic_distribution_strategy(): void
    {
        $filesystem = new AdapterFilesystem(new LocalAdapter(self::TEMP_DIR), [
            'url_prefixes' => ['https://sub1.example.com', 'https://sub2.example.com'],
        ]);
        $filesystem->write('some-file.txt', 'contents');
        $filesystem->write('baz.txt', 'contents');

        $this->assertSame('https://sub2.example.com/some-file.txt', $filesystem->file('some-file.txt')->url()->toString());
        $this->assertSame('https://sub1.example.com/baz.txt', $filesystem->file('baz.txt')->url()->toString());
    }

    protected function createFilesystem(): Filesystem
    {
        return new AdapterFilesystem(new LocalAdapter(self::TEMP_DIR), [
            'url_prefix' => 'https://example.com/sub/',
        ]);
    }
}
