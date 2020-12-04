<?php

namespace App\Form\InternationalSurvey\Trip;

use Symfony\Component\OptionsResolver\OptionsResolver;

class OutboundCargoStateType extends AbstractCargoStateType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'direction' => 'outbound',
            'validation_groups' => ['trip_outbound_cargo_state'],
        ]);
    }
}