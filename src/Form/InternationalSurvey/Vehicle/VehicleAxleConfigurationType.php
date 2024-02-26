<?php

namespace App\Form\InternationalSurvey\Vehicle;

use App\Entity\International\Vehicle;
use App\Form\AbstractVehicleAxleConfigurationType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleAxleConfigurationType extends AbstractVehicleAxleConfigurationType
{
    #[\Override]
    protected function getVehicle($formData): Vehicle
    {
        return $formData;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
            'axle_configuration_label' => 'international.vehicle.vehicle-axle-configuration.axle-configuration.label',
            'axle_configuration_help' => 'international.vehicle.vehicle-axle-configuration.axle-configuration.help',
        ]);
    }
}
