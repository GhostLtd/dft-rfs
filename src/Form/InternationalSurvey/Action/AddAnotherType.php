<?php

namespace App\Form\InternationalSurvey\Action;

use App\Form\AbstractAddAnotherType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAnotherType extends AbstractAddAnotherType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.action.add-another',
        ]);
    }
}