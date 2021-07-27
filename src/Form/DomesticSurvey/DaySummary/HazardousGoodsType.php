<?php


namespace App\Form\DomesticSurvey\DaySummary;


use App\Form\AbstractHazardousGoodsType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HazardousGoodsType extends AbstractHazardousGoodsType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_entity_key', 'domestic.day-summary');
    }
}