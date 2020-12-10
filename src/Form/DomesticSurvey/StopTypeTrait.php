<?php

namespace App\Form\DomesticSurvey;

use Symfony\Component\OptionsResolver\OptionsResolver;

trait StopTypeTrait
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired("translation_entity_key");
        $resolver->setAllowedValues("translation_entity_key", ['day-summary', 'day-stop']);
    }
}