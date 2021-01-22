<?php

namespace App\Form\InternationalSurvey\Action;

use App\Form\AbstractConfirmType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAnotherType extends AbstractConfirmType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.action.add-another',
        ]);
    }
}