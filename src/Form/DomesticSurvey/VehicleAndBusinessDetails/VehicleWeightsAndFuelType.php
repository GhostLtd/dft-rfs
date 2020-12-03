<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Volume;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class VehicleWeightsAndFuelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.survey-response.vehicle-weights-and-fuel";
        $builder
            ->add('fuelQuantity', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.fuel-quantity.label",
                'help' => "{$translationKeyPrefix}.fuel-quantity.help",
                'property_path' => 'vehicle.fuelQuantity',
                'label_attr' => ['class' => 'govuk-label--s'],
                'value_options' => [
                    'label' => 'Quantity',
                    'is_decimal' => true,
                    'attr' => ['class' => 'govuk-input--width-5']
                ],
                'unit_options' => [
                    'label' => 'Unit',
                    'choices' => Volume::UNIT_CHOICES,
                ],
            ])
            ->add('grossWeight', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.gross-weight.label",
                'help' => "{$translationKeyPrefix}.gross-weight.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'vehicle.grossWeight',
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
            ->add('carryingCapacity', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.carrying-capacity.label",
                'help' => "{$translationKeyPrefix}.carrying-capacity.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'vehicle.carryingCapacity',
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['vehicle_weight', 'vehicle_fuel_quantity'],
        ]);
    }
}
