<?php

namespace App\Form\Domestic;

use App\Entity\DomesticSurveyResponse;
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
        $builder
            ->add('fuelQuantity', ValueUnitType::class, [
                'label' => 'survey.domestic.forms.vehicle-weights-and-fuel.fuel-quantity.label',
                'property_path' => 'vehicle.fuelQuantity',
                'label_attr' => ['class' => 'govuk-label--m'],
                'value_options' => [
                    'label' => 'Quantity',
                    'is_decimal' => true,
                    'attr' => ['class' => 'govuk-input--width-5']
                ],
                'unit_options' => [
                    'label' => 'Units',
                    'choices' => Volume::UNIT_CHOICES,
                ],
            ])
            ->add('grossWeight', Gds\NumberType::class, [
                'label' => 'survey.domestic.forms.vehicle-weights-and-fuel.gross-weight.label',
                'help' => 'survey.domestic.forms.vehicle-weights-and-fuel.gross-weight.help',
                'label_attr' => ['class' => 'govuk-label--m'],
                'property_path' => 'vehicle.grossWeight',
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
            ->add('carryingCapacity', Gds\NumberType::class, [
                'label' => 'survey.domestic.forms.vehicle-weights-and-fuel.carrying-capacity.label',
                'help' => 'survey.domestic.forms.vehicle-weights-and-fuel.carrying-capacity.help',
                'label_attr' => ['class' => 'govuk-label--m'],
                'property_path' => 'vehicle.carryingCapacity',
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DomesticSurveyResponse::class,
        ]);
    }
}
