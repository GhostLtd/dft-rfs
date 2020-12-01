<?php


namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Form\AbstractVehicleBodyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleBodyType extends AbstractVehicleBodyType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'domestic.survey-response',
            'property_path' => 'vehicle.bodyType',
        ]);
    }
}