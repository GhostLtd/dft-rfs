<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType as ExtendedTextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextType extends ExtendedTextType
{
    public function getParent()
    {
        return ExtendedTextType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_text';
    }
}
