<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\Vehicle;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeAndConfigurationChoices = [
            'Rigid' => Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_RIGID],
            'Rigid with trailer' => Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_RIGID_TRAILER],
            'Articulated' => Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_ARTICULATED],
        ];

        $addPlaceholder = function(array $choices) use ($options):array {
            if (!$options['placeholders']) {
                return $choices;
            }

            return array_merge(['' => null], $choices);
        };

        $builder
            ->add('registrationMark', Gds\InputType::class, [
                'label' => "Registration Mark",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('axleConfiguration', Gds\ChoiceType::class, [
                'label' => "Type/configuration",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => $addPlaceholder($typeAndConfigurationChoices),
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
            ])
            ->add('operationType', Gds\ChoiceType::class, [
                'label' => 'Operation type',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => $addPlaceholder(Vehicle::OPERATION_TYPE_CHOICES),
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
            ])
            ->add('bodyType', Gds\ChoiceType::class, [
                'label' => 'Vehicle/trailer body type',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => $addPlaceholder(Vehicle::BODY_CONFIGURATION_CHOICES),
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
            ])
            ->add('grossWeight', Gds\IntegerType::class, [
                'label' => "Gross vehicle weight",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
            ->add('carryingCapacity', Gds\IntegerType::class, [
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['admin_vehicle'],
            'placeholders' => false,
        ]);
    }
}
