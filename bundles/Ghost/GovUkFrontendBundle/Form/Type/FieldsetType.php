<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldsetType extends AbstractType
{
    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'gds_fieldset';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'compound' => true,
            'inherit_data' => true,
            'error_bubbling' => false,
        ]);
    }
}
