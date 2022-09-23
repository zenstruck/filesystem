<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\File as FoundationFile;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class FilesystemFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function(FormEvent $event) use ($options) {
                if ($formData = $event->getData()) {
                    if ($options['multiple']) {
                        $data = [];
                        foreach ($formData as $file) {
                            if ($file instanceof FoundationFile) {
                                $data[] = new PendingFile($file, $options['filesystem_options']);
                            }
                        }
                        $event->setData($data);
                    } elseif ($formData instanceof FoundationFile) {
                        $event->setData(
                            new PendingFile($formData, $options['filesystem_options'])
                        );
                    }
                }
            },
            -10
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => fn(Options $options) => $options['multiple'] ? null : File::class,
            'filesystem_options' => [],
        ]);

        $resolver->setAllowedTypes('filesystem_options', 'array');
    }

    public function getParent(): string
    {
        return FileType::class;
    }
}
