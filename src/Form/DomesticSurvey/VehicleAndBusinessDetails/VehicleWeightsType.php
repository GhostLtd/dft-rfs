<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class VehicleWeightsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.survey-response.vehicle-weights";
        $builder
            ->add('grossWeight', Gds\IntegerType::class, [
                'label' => "{$translationKeyPrefix}.gross-weight.label",
                'help' => "{$translationKeyPrefix}.gross-weight.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'vehicle.grossWeight',
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
            ->add('carryingCapacity', Gds\IntegerType::class, [
                'label' => "{$translationKeyPrefix}.carrying-capacity.label",
                'help' => "{$translationKeyPrefix}.carrying-capacity.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'vehicle.carryingCapacity',
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['vehicle_weight'],
        ]);
    }
}
