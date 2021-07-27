<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Form\Admin\AbstractDeleteType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DaySummaryDeleteType extends AbstractDeleteType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'domestic.day-summary.delete'
        ]);
    }
}