<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Form\Admin\AbstractDeleteType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripDeleteType extends AbstractDeleteType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.trip.delete'
        ]);
    }
}