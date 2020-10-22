<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ButtonType as ExtendedButtonType;

class ButtonType extends ExtendedButtonType
{
    public function getParent()
    {
        return ExtendedButtonType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_button';
    }
}
