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
            'validation_groups' => ['trip_return_ports', 'trip_return_cargo_state'],
            'at_capacity_null_message' => 'international.trip.return.at-capacity-not-null',
        ]);
    }
}