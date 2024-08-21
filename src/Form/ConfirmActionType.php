<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfirmActionType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = $options['translation_key_prefix'];

        $builder
            ->add('confirm', Gds\ButtonType::class, array_merge($options['confirm_button_options'], [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.confirm.label",
            ]))
            ->add('cancel', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.cancel.label",
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'confirm_button_options' => [],
        ]);
        $resolver->setRequired([
            'translation_key_prefix',
        ]);
    }
}
