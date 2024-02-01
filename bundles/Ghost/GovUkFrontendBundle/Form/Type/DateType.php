<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateType as ExtendedDateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateType extends ExtendedDateType
{
    public function getBlockPrefix(): string
    {
        return 'gds_date';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'widget' => 'text',
            'invalid_message' => 'common.date.invalid',
        ]);
        $resolver->setAllowedValues('widget', ['text']);

        $resolver->setDefault('format', 'dd-MM-yyyy');
    }
}
