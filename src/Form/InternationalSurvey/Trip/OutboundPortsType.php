<?php

namespace App\Form\InternationalSurvey\Trip;

use Symfony\Component\OptionsResolver\OptionsResolver;

class OutboundPortsType extends AbstractPortsType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'direction' => 'outbound',
            'validation_groups' => ['trip_outbound_ports'],
        ]);
    }
}