<?php

namespace Zenstruck\Filesystem\Feature\TransformImageUrl;

use League\Glide\Urls\UrlBuilder;
use Zenstruck\Filesystem\Feature\TransformImageUrl;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Uri;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class GlideTransformImageUrl implements TransformImageUrl
{
    public function __construct(private UrlBuilder $urlBuilder)
    {
    }

    public function transformUrlFor(Image $image, mixed $options = []): Uri
    {
        return Uri::new($this->urlBuilder->getUrl($image->path(), $options));
    }
}
