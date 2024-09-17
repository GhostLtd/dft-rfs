<?php

namespace App\Form\DomesticSurvey\ClosingDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\DecimalType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleFuelType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fuelTypeChoices = array_combine(
            array_map(fn($key) => "domestic.survey-response.vehicle-fuel.type.$key.label", array_keys(Vehicle::FUEL_TYPES)),
            array_keys(Vehicle::FUEL_TYPES)
        );
        $fuelTypeChoiceOptions = array_combine(
            array_keys($fuelTypeChoices),
            array_map(fn($type) => [
                'help' => "domestic.survey-response.vehicle-fuel.type.$type.help",
                'conditional_form_name' => "fuelDetails_$type",
            ], array_keys(Vehicle::FUEL_TYPES))
        );

        $builder
            ->setDataMapper(new VehicleFuelTypeMapper())
            ->add('fuelType', ChoiceType::class, [
                'label' => "domestic.survey-response.vehicle-fuel.fuel-type.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--l'],
                'label_is_page_heading' => true,
                'help' => "domestic.survey-response.vehicle-fuel.fuel-type.help",
                'property_path' => 'vehicle.fuelType',
                'choices' => $fuelTypeChoices,
                'choice_options' => $fuelTypeChoiceOptions,
            ])
        ;
        foreach (Vehicle::FUEL_TYPES as $type => $unit) {
            $builder->add("fuelDetails_$type", FieldsetType::class, [
                'inherit_data' => true,
                'label' => false,
            ]);
            $subForm = $builder->get("fuelDetails_$type");
            if (in_array($type, ['petrol', 'diesel'])) {
                $subForm->add("fuelTypeIsHybrid_$type", BooleanChoiceType::class, [
                    'label' => "domestic.survey-response.vehicle-fuel.type.$type.is-hybrid.label",
                    'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                    'help' => "domestic.survey-response.vehicle-fuel.type.$type.is-hybrid.help",
                    'mapped' => false,
                    'inline' => true,
                ]);
            }
            switch ($type) {
                case 'other' :
                    $subForm->add("fuelQuantity_other", TextareaType::class, [
                        'label' => "domestic.survey-response.vehicle-fuel.type.$type.quantity.label",
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'help' => "domestic.survey-response.vehicle-fuel.type.$type.quantity.help",
                        'help_html' => 'markdown',
                        'mapped' => false,
                    ]);
                    break;

                default:
                    $subForm->add("fuelQuantity_$type", DecimalType::class, [
                        'mapped' => false,
                        'label' => "domestic.survey-response.vehicle-fuel.type.$type.quantity.label",
                        'label_attr' => ['class' => 'govuk-label--s'],
                        'help' => "domestic.survey-response.vehicle-fuel.type.$type.quantity.help",
                        'precision' => 8,
                        'scale' => 2,
                        'suffix' => $unit,
                        'attr' => ['class' => 'govuk-input--width-5']
                    ]);
            }
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['vehicle_fuel'],
            'error_bubbling' => false,
            'error_mapping' => [
                'vehicle.fuelQuantity_diesel' => 'fuelDetails_diesel.fuelQuantity_diesel',
                'vehicle.fuelTypeIsHybrid_diesel' => 'fuelDetails_diesel.fuelTypeIsHybrid_diesel',

                'vehicle.fuelQuantity_petrol' => 'fuelDetails_petrol.fuelQuantity_petrol',
                'vehicle.fuelTypeIsHybrid_petrol' => 'fuelDetails_petrol.fuelTypeIsHybrid_petrol',

                'vehicle.fuelQuantity_gas' => 'fuelDetails_gas.fuelQuantity_gas',
                'vehicle.fuelQuantity_biofuel' => 'fuelDetails_biofuel.fuelQuantity_biofuel',
                'vehicle.fuelQuantity_electric' => 'fuelDetails_electric.fuelQuantity_electric',
                'vehicle.fuelQuantity_fuel_cell' => 'fuelDetails_fuel_cell.fuelQuantity_fuel_cell',

                'vehicle.fuelQuantity_other' => 'fuelDetails_other.fuelQuantity_other',
            ],
        ]);
    }
}
