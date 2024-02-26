<?php

namespace App\Form\InternationalSurvey\Vehicle;

use App\Entity\International\Vehicle;
use App\Form\AbstractVehicleBodyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleBodyType extends AbstractVehicleBodyType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'translation_entity_key' => 'international.vehicle',
        ]);
    }
}
