<?php


namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Form\AbstractVehicleAxleConfigurationType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleAxleConfigurationType extends AbstractVehicleAxleConfigurationType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'domestic.survey-response',
            'property_path' => 'vehicle.axleConfiguration',
        ]);
    }
}
