<?php

namespace App\Form\InternationalSurvey\Trip;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ReturnPortsAndCargoStateType extends AbstractPortsAndCargoStateType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'direction' => 'return',
            'validation_groups' => ['trip_return_ports'],
        ]);
    }
}