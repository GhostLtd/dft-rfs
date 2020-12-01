<?php


namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Form\AbstractVehicleTrailerConfigurationType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleTrailerConfigurationType extends AbstractVehicleTrailerConfigurationType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'domestic.survey-response',
            'property_path' => 'vehicle.trailerConfiguration',
        ]);
    }
}