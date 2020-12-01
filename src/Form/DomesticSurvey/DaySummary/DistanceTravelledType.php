<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Distance;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\StopTrait;
use App\Form\DomesticSurvey\StopTypeTrait;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DistanceTravelledType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $valueOptions = [
            'label' => 'Distance',
            'is_decimal' => true,
            'attr' => ['class' => 'govuk-input--width-5'],
        ];
        $unitOptions = [
            'label' => 'Unit',
            'choices' => Distance::UNIT_CHOICES,
        ];

        $translationKeyPrefix = "domestic.day-summary.distance-travelled";
        $builder
            ->add('distanceTravelledLoaded', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled-loaded.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.distance-travelled-loaded.help",
                'value_options' => $valueOptions,
                'unit_options' => $unitOptions,
                'data_class' => Distance::class,
            ])
            ->add('distanceTravelledUnloaded', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled-unloaded.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.distance-travelled-unloaded.help",
                'value_options' => $valueOptions,
                'unit_options' => $unitOptions,
                'data_class' => Distance::class,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
        ]);
    }
}
