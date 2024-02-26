<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Form\AbstractVehicleBodyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleBodyType extends AbstractVehicleBodyType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'translation_entity_key' => 'domestic.survey-response',
            'property_path' => 'vehicle.bodyType',
        ]);
    }
}
