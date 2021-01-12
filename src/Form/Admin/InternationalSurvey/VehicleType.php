<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\Vehicle;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $typeAndConfigurationChoices = [
            'Rigid' => Vehicle::AXLE_CONFIGURATION_CHOICES[100],
            'Rigid with trailer' => Vehicle::AXLE_CONFIGURATION_CHOICES[200],
            'Articulated' => Vehicle::AXLE_CONFIGURATION_CHOICES[300],
        ];

        $builder
            ->add('registrationMark', Gds\InputType::class, [
                'label' => "Registration Mark",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('axleConfiguration', Gds\ChoiceType::class, [
                'label' => "Type/configuration",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => $typeAndConfigurationChoices,
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
            ])
            ->add('operationType', Gds\ChoiceType::class, [
                'label' => 'Operation type',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => Vehicle::OPERATION_TYPE_CHOICES,
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
            ])
            ->add('bodyType', Gds\ChoiceType::class, [
                'label' => 'Vehicle/trailer body type',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => Vehicle::BODY_CONFIGURATION_CHOICES,
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
            ])
            ->add('grossWeight', Gds\NumberType::class, [
                'label' => "Gross vehicle weight",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
            ->add('carryingCapacity', Gds\NumberType::class, [
                'label' => "Carrying capacity",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
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
            'validation_groups' => ['admin_vehicle'],
        ]);
    }
}