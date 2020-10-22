<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\SubmitType as ExtendedSubmitType;

class SubmitType extends ExtendedSubmitType
{
    public function getParent()
    {
        return ExtendedSubmitType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_submit';
    }
}
