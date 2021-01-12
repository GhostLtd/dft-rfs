<?php

namespace App\Form\Admin\InternationalSurvey;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CorrespondenceDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contactName', Gds\InputType::class, [
                'label' => "Name",
                'label_attr' => [
                    'class' => 'govuk-label--s',
                ],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('contactEmail', Gds\EmailType::class, [
                'label' => "Email",
                'label_attr' => [
                    'class' => 'govuk-label--s',
                ],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('contactTelephone', Gds\InputType::class, [
                'label' => "Phone",
                'label_attr' => [
                    'class' => 'govuk-label--s',
                ],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['admin_correspondence'],
        ]);
    }
}