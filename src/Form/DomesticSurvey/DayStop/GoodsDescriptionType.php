<?php


namespace App\Form\DomesticSurvey\DayStop;


use App\Form\DomesticSurvey\AbstractGoodsDescriptionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoodsDescriptionType extends AbstractGoodsDescriptionType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'day-stop',
            'is_summary_day' => false,
        ]);
    }
}