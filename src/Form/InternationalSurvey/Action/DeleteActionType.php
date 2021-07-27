<?php

namespace App\Form\InternationalSurvey\Action;

use App\Form\Admin\AbstractDeleteType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeleteActionType extends AbstractDeleteType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_domain' => 'messages',
            'translation_key_prefix' => 'international.action.delete'
        ]);
    }
}