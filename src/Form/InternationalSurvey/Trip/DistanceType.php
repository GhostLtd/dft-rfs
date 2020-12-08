<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\Distance;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DistanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = 'international.trip.distance';

        $builder
            ->add('roundTripDistance', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.distance.help",
                'value_options' => [
                    'label' => 'Distance',
                    'is_decimal' => true,
                    'attr' => ['class' => 'govuk-input--width-5'],
                ],
                'unit_options' => [
                    'label' => 'common.value-unit.unit',
                    'choices' => Distance::UNIT_CHOICES,
                ],
                'data_class' => Distance::class,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['trip_distance'],
        ]);
    }
}