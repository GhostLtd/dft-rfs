<?php

namespace App\Form\Admin\RoRo;

use App\Form\RoRo\IntroductionType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditIsActiveStateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'for_admin' => true,
        ]);
    }

    public function getParent(): string
    {
        return IntroductionType::class;
    }
}