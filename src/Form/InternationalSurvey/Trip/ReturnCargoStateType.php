<?php

namespace App\Form\InternationalSurvey\Trip;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ReturnCargoStateType extends AbstractCargoStateType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'direction' => 'return',
            'validation_groups' => ['trip_return_cargo_state'],
        ]);
    }
}