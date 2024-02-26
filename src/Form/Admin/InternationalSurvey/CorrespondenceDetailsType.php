<?php

namespace App\Form\Admin\InternationalSurvey;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CorrespondenceDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contactName', Gds\InputType::class, [
                'label' => "Name",
                'label_attr' => [
                    'class' => 'govuk-label--s',
                ],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('contactTelephone', Gds\InputType::class, [
                'label' => "Telephone",
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
            ]);

        if ($options['include_buttons']) {
            $builder
                ->add('submit', Gds\ButtonType::class, [
                    'label' => 'Save changes',
                ])
                ->add('cancel', Gds\ButtonType::class, [
                    'label' => 'Cancel',
                    'attr' => ['class' => 'govuk-button--secondary'],
                ]);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['admin_correspondence'],
            'include_buttons' => true,
        ]);
    }
}
