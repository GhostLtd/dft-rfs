<?php

namespace App\Form\InternationalSurvey\Vehicle;

use App\Entity\International\Vehicle;
use App\Form\AbstractVehicleAxleConfigurationType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleAxleConfigurationType extends AbstractVehicleAxleConfigurationType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'translation_entity_key' => 'international.vehicle',
        ]);
    }
}
