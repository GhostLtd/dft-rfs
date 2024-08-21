<?php

namespace App\Form\Admin\DomesticSurvey\Edit;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleWeightsType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class VehicleDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $axleConfigurationChoices = [
            'Rigid' => Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_RIGID],
            'Rigid with trailer' => Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_RIGID_TRAILER],
            'Articulated' => Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_ARTICULATED],
        ];

        $bodyTypeChoices = Vehicle::BODY_CONFIGURATION_CHOICES;

        if ($options['add_form']) {
            $axleConfigurationChoices = ['' => null] + $axleConfigurationChoices;
            $bodyTypeChoices = ['' => null] + $bodyTypeChoices;
        }

        $builder
            ->add('weights', VehicleWeightsType::class, [
                'inherit_data' => true,
                'label' => false,
                'constraints' => [new Valid()],
                'validation_groups' => $options['validation_groups'],
            ])
            ->add('axleConfiguration', Gds\ChoiceType::class, [
                'label' => "Type/configuration",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => $axleConfigurationChoices,
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
                'property_path' => 'vehicle.axleConfiguration',
            ])
            ->add('bodyType', Gds\ChoiceType::class, [
                'label' => 'Vehicle/trailer body type',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => $bodyTypeChoices,
                'expanded' => false,
                'attr' => ['class' => 'govuk-select--width-15'],
                'property_path' => 'vehicle.bodyType',
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
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['admin_vehicle'],
            'include_buttons' => true,
            'add_form' => false,
        ]);
    }
}
