<?php

namespace App\Form\InternationalSurvey\Action;

use App\Form\AbstractConfirmType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAnotherType extends AbstractConfirmType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.action.add-another',
            'null_message' => 'international.action.add-another.not-null'
        ]);
    }
}
