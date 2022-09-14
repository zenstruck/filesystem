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
        $options = match(true) {
            \is_string($options) => ['p' => $options], // is glide "preset"
            \is_array($options) && !array_is_list($options) => $options, // is standard glide parameters
            \is_array($options) => ['p' => \implode(',', $options)], // is array of "presets"
            default => throw new \LogicException('invalid options... todo'),
        };

        return Uri::new($this->urlBuilder->getUrl($image->path(), $options));
    }
}
