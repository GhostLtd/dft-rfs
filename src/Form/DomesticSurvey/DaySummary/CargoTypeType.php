<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Form\AbstractCargoTypeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CargoTypeType extends AbstractCargoTypeType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'domestic.day-summary',
        ]);
    }
}
