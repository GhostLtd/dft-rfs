<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Distance;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\StopTrait;
use App\Form\DomesticSurvey\AbstractDistanceTravelledType;
use App\Form\DomesticSurvey\StopTypeTrait;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DistanceTravelledType extends AbstractDistanceTravelledType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.day-summary.distance-travelled";
        $builder
            ->add('distanceTravelledLoaded', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled-loaded.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.distance-travelled-loaded.help",
                'value_options' => self::VALUE_OPTIONS,
                'unit_options' => self::UNIT_OPTIONS,
                'data_class' => Distance::class,
            ])
            ->add('distanceTravelledUnloaded', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled-unloaded.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.distance-travelled-unloaded.help",
                'value_options' => self::VALUE_OPTIONS,
                'unit_options' => self::UNIT_OPTIONS,
                'data_class' => Distance::class,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
            'validation_groups' => 'day-summary.distance-travelled',
        ]);
    }
}
