<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Form\AbstractVehicleAxleConfigurationType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleAxleConfigurationType extends AbstractVehicleAxleConfigurationType
{
    #[\Override]
    protected function getVehicle($formData): Vehicle
    {
        return $formData->getVehicle();
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'property_path' => 'vehicle.axleConfiguration',

            'axle_configuration_label' => 'domestic.survey-response.vehicle-axle-configuration.axle-configuration.label',
            'axle_configuration_help' => 'domestic.survey-response.vehicle-axle-configuration.axle-configuration.help',
        ]);
    }
}
