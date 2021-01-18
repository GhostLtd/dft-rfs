<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Form\Admin\AbstractDeleteType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurveyDeleteType extends AbstractDeleteType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.survey.delete'
        ]);
    }
}