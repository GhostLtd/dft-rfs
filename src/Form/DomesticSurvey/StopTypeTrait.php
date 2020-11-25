<?php


namespace App\Form\DomesticSurvey;


use App\Entity\Domestic\StopTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait StopTypeTrait
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StopTrait::class,
        ]);
        $resolver->setRequired("translation_entity_key");
        $resolver->setAllowedValues("translation_entity_key", ['day-summary', 'day-stop']);
    }

}