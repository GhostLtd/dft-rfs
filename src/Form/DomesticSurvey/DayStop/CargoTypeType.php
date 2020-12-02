<?php


namespace App\Form\DomesticSurvey\DayStop;


use App\Form\AbstractCargoTypeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CargoTypeType extends AbstractCargoTypeType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'day-stop',
            'translation_namespace_key' => 'domestic',
            'is_summary_day' => false,
        ]);

    }
}