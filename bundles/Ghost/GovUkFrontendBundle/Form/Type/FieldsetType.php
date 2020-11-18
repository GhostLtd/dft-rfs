<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldsetType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'gds_fieldset';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'compound' => true,
            'inherit_data' => true,
            'error_bubbling' => false,
        ]);
    }
}