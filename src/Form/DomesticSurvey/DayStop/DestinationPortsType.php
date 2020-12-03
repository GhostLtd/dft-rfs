<?php


namespace App\Form\DomesticSurvey\DayStop;


use App\Form\DomesticSurvey\AbstractDestinationPortsType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DestinationPortsType extends AbstractDestinationPortsType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('translation_entity_key', 'day-stop');
    }
}