<?php


namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Form\AbstractVehicleAxleConfigurationType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleAxleConfigurationType extends AbstractVehicleAxleConfigurationType
{
    protected function getVehicle($formData)
    {
        return $formData->getVehicle();
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'translation_entity_key' => 'domestic.survey-response',
            'property_path' => 'vehicle.axleConfiguration',
        ]);
    }
}
