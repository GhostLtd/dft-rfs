<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType as ExtendedTextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InputType extends ExtendedTextType
{
    public function getBlockPrefix()
    {
        return 'gds_input';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
    }
}
