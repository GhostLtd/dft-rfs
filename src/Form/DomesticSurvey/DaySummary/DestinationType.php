<?php


namespace App\Form\DomesticSurvey\DaySummary;


use App\Form\DomesticSurvey\AbstractDestinationType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DestinationType extends AbstractDestinationType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_entity_key', 'day-summary');
    }
}