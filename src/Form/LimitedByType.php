<?php


namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LimitedByType extends ChoiceType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $prefix = 'common.limited-by';
        $choicesPrefix = "{$prefix}.choices";

        $resolver->setDefaults([
            'multiple' => true,
            'label' => "{$prefix}.label",
            'help' => "{$prefix}.help",
            'choices' => [
                "{$choicesPrefix}.weight" => "weight",
                "{$choicesPrefix}.space" => "space",
            ],
        ]);
    }
}