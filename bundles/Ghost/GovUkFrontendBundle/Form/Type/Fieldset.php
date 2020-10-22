<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Fieldset extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'inherit_data' => true,
            'label_is_page_heading' => false,
            'error_bubbling' => false,
        ]);

        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);
    }
}