<?php


namespace App\Form\DomesticSurvey\DayStop;


use App\Form\DomesticSurvey\AbstractOriginType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OriginType extends AbstractOriginType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_entity_key', 'day-stop');
    }
}