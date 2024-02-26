<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfirmationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('yes', ButtonType::class, [
            'label' => $options['yes_label'],
            'disabled' => $options['yes_disabled'],
        ]);

        if ($options['no_enabled']) {
            $builder->add('no', ButtonType::class, [
                'label' => $options['no_label'],
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('yes_label');

        $resolver->setDefaults([
            'no_label' => 'common.actions.cancel',
            'no_enabled' => true,
            'yes_disabled' => false,
        ]);

        $resolver->setAllowedValues('no_enabled', [true, false]);
    }
}
