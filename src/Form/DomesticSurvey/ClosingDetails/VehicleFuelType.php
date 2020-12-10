<?php

namespace App\Form\DomesticSurvey\ClosingDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Volume;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class VehicleFuelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.survey-response.vehicle-fuel";
        $builder
            ->add('fuelQuantity', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.fuel-quantity.label",
                'help' => "{$translationKeyPrefix}.fuel-quantity.help",
                'property_path' => 'vehicle.fuelQuantity',
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'label_is_page_heading' => true,
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['vehicle_fuel_quantity'],
        ]);
    }
}
