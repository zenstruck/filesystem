<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            eventName: FormEvents::PRE_SUBMIT,
            listener: static function(FormEvent $event) use ($options) {
                if (!$formData = $event->getData()) {
                    return;
                }

                if (!$options['multiple']) {
                    if ($formData instanceof File) {
                        $event->setData(self::pendingFileFactory($options, $formData));
                    }

                    return;
                }

                $data = [];

                foreach ($formData as $file) {
                    if ($file instanceof File) {
                        $data[] = self::pendingFileFactory($options, $file);
                    }
                }

                $event->setData($data);
            },
            priority: -10
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => function(Options $options) {
                    if ($options['multiple']) {
                        return null;
                    }

                    return $options['image'] ? PendingImage::class : PendingFile::class;
                },
                'image' => false,
            ])
            ->setAllowedTypes('image', 'bool')
        ;
    }

    public function getParent(): string
    {
        return FileType::class;
    }

    private static function pendingFileFactory(array $options, \SplFileInfo $file): PendingFile
    {
        return $options['image'] ? new PendingImage($file) : new PendingFile($file);
    }
}
