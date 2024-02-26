<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LimitedByType extends AbstractType
{
    #[\Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $prefix = 'common.limited-by';
        $choicesPrefix = "{$prefix}.choices";

        $resolver->setDefaults([
            'multiple' => true,
            'expanded' => true,
            'label' => "{$prefix}.label",
            'help' => "{$prefix}.help",
            'choices' => [
                "{$choicesPrefix}.space" => "space",
                "{$choicesPrefix}.weight" => "weight",
            ],
        ]);
    }
}
