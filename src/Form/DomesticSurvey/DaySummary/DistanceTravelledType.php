<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Distance;
use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\AbstractDistanceTravelledType;
use Ghost\GovUkFrontendBundle\Form\Type\ValueUnitType;
use Ghost\GovUkFrontendBundle\Form\Type\DecimalType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DistanceTravelledType extends AbstractDistanceTravelledType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.day-summary.distance-travelled";
        $builder
            ->add('distanceTravelledLoaded', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled-loaded.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "{$translationKeyPrefix}.distance-travelled-loaded.help",
                'value_form_type' => DecimalType::class,
                'value_options' => self::VALUE_OPTIONS,
                'unit_options' => self::UNIT_OPTIONS,
                'data_class' => Distance::class,
            ])
            ->add('distanceTravelledUnloaded', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled-unloaded.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "{$translationKeyPrefix}.distance-travelled-unloaded.help",
                'value_form_type' => DecimalType::class,
                'value_options' => self::VALUE_OPTIONS,
                'unit_options' => self::UNIT_OPTIONS,
                'data_class' => Distance::class,
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
            'validation_groups' => 'day-summary.distance-travelled',
        ]);
    }
}
