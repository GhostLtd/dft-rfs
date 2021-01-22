<?php


namespace App\Form\InternationalSurvey\Consignment;


use App\Form\AbstractConfirmType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAnotherType extends AbstractConfirmType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.consignment.add-another',
        ]);
    }
}