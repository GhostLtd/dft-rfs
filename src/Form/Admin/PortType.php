<?php

namespace App\Form\Admin;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PortType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', InputType::class, [
                'label' => 'Name',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('code', IntegerType::class, [
                'label' => 'Code',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('submit', ButtonType::class, [
                'type' => 'submit',
                'label' => 'Save',
            ])
            ->add('cancel', ButtonType::class, [
                'type' => 'submit',
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'add' => false,
        ]);

        $resolver->setAllowedTypes('add', 'bool');

        $resolver->setDefault('validation_groups', function(Options $options) {
            $groups = $options['add'] ? 'admin_add_port' : 'admin_edit_port';
            return [$groups];
        });
    }
}
