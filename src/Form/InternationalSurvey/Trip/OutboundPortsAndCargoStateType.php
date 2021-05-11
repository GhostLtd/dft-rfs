<?php

namespace App\Form\InternationalSurvey\Trip;

use Symfony\Component\OptionsResolver\OptionsResolver;

class OutboundPortsAndCargoStateType extends AbstractPortsAndCargoStateType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'direction' => 'outbound',
            'validation_groups' => ['trip_outbound_ports', 'trip_outbound_cargo_state'],
            'at_capacity_null_message' => 'international.trip.outbound.at-capacity-not-null',
        ]);
    }
}