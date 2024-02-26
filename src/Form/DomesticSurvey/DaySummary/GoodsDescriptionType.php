<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Form\AbstractGoodsDescriptionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoodsDescriptionType extends AbstractGoodsDescriptionType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'domestic.day-summary',
            'is_summary_day' => true,
        ]);
    }
}
