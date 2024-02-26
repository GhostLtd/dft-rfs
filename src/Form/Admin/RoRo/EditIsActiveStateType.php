<?php

namespace App\Form\Admin\RoRo;

use App\Form\RoRo\IntroductionType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditIsActiveStateType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comments', TextareaType::class, [
                'row_attr' => ['class' => 'govuk-!-margin-top-8'],
                'label' => 'Respondent comments',
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => 'These are comments left by the user who filled the survey',
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
            'show_data_entry_question' => false,
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return IntroductionType::class;
    }
}
