<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAnotherType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('another', ChoiceType::class, [
                'label' => $options['another_label'],
                'help' => $options['another_help'],
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    "common.choices.boolean.yes" => true,
                    "common.choices.boolean.no" => false,
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-radios--inline'],
            ])->add('submit', ButtonType::class, [
                'label' => 'common.actions.continue',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'another_label' => false,
            'another_help' => false,
        ]);
    }
}