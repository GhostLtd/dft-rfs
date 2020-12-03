<?php


namespace App\Form\DomesticSurvey\DayStop;


use App\Form\DomesticSurvey\AbstractOriginPortsType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OriginPortsType extends AbstractOriginPortsType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_entity_key', 'day-stop');
    }
}