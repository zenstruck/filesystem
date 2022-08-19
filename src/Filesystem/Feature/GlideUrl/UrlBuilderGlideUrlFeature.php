<?php

namespace Zenstruck\Filesystem\Feature\GlideUrl;

use League\Glide\Urls\UrlBuilder;
use Zenstruck\Filesystem\Feature\GlideUrl;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Uri;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class UrlBuilderGlideUrlFeature implements GlideUrl
{
    public function __construct(private UrlBuilder $urlBuilder)
    {
    }

    public function glideUrlFor(Image $image, mixed $options = []): Uri
    {
        return Uri::new($this->urlBuilder->getUrl($image->path(), $options));
    }
}
