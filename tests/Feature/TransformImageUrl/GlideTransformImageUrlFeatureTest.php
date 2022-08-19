<?php

namespace Zenstruck\Filesystem\Tests\Feature\TransformImageUrl;

use League\Glide\Urls\UrlBuilder;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\LocalAdapter;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Feature\TransformImageUrl\GlideTransformImageUrl;
use Zenstruck\Filesystem\Tests\FilesystemTest;
use Zenstruck\Uri;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class GlideTransformImageUrlFeatureTest extends FilesystemTest
{
    /**
     * @test
     */
    public function can_build_url(): void
    {
        $filesystem = $this->createFilesystem();
        $image = $filesystem->write('foo.png', '')->image('foo.png');

        $this->assertSame('/glide/foo.png', $image->transformUrl()->toString());
        $this->assertSame('/glide/foo.png?w=100&h=100', $image->transformUrl(['w' => 100, 'h' => 100])->toString());
    }

    protected function createFilesystem(): Filesystem
    {
        $urlBuilder = $this->createMock(UrlBuilder::class);

        $urlBuilder->expects($this->atMost(2))
            ->method('getUrl')
            ->willReturn(
                Uri::new('/glide/foo.png'),
                Uri::new('/glide/foo.png?w=100&h=100')
            )
        ;

        return new AdapterFilesystem(new LocalAdapter(self::TEMP_DIR), [
            'image_check_mime' => false
        ], [
            new GlideTransformImageUrl($urlBuilder),
        ]);
    }
}
