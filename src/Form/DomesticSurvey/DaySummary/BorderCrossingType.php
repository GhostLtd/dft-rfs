<?php


namespace App\Form\DomesticSurvey\DaySummary;


use App\Form\DomesticSurvey\AbstractBorderCrossingType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BorderCrossingType extends AbstractBorderCrossingType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_entity_key', 'day-summary');
    }
}