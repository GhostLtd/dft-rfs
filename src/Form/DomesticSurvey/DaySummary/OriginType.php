<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Form\DomesticSurvey\AbstractOriginType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OriginType extends AbstractOriginType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_entity_key', 'day-summary');
    }
}
