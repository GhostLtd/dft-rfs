<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Form\AbstractHazardousGoodsType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HazardousGoodsType extends AbstractHazardousGoodsType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_entity_key', 'domestic.day-summary');
    }
}
