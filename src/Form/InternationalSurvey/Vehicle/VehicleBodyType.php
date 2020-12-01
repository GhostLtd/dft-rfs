<?php


namespace App\Form\InternationalSurvey\Vehicle;

use App\Entity\International\Vehicle;
use App\Form\AbstractVehicleBodyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleBodyType extends AbstractVehicleBodyType
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