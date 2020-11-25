<?php


namespace App\Form\DomesticSurvey\DaySummary;


use App\Form\DomesticSurvey\AbstractGoodsDescriptionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoodsDescriptionType extends AbstractGoodsDescriptionType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'day-summary',
            'is_summary_day' => true,
        ]);
    }
}