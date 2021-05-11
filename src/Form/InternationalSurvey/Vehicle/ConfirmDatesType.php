<?php

namespace App\Form\InternationalSurvey\Vehicle;

use App\Form\AbstractConfirmType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfirmDatesType extends AbstractConfirmType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.vehicle.confirm-dates',
            'null_message' => 'international.vehicle.confirm-dates.not-null',
        ]);
    }
}