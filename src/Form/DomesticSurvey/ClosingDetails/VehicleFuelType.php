<?php

namespace App\Form\DomesticSurvey\ClosingDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Volume;
use Ghost\GovUkFrontendBundle\Form\Type\ValueUnitType;
use Ghost\GovUkFrontendBundle\Form\Type\DecimalType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleFuelType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.survey-response.vehicle-fuel";
        $builder
            ->add('fuelQuantity', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.fuel-quantity.label",
                'help' => "{$translationKeyPrefix}.fuel-quantity.help",
                'property_path' => 'vehicle.fuelQuantity',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'value_form_type' => DecimalType::class,
                'value_options' => [
                    'label' => 'Quantity',
                    'precision' => 8,
                    'scale' => 2,
                    'attr' => ['class' => 'govuk-input--width-5']
                ],
                'unit_options' => [
                    'label' => 'Unit',
                    'choices' => Volume::UNIT_CHOICES,
                ],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['vehicle_fuel_quantity'],
        ]);
    }
}
