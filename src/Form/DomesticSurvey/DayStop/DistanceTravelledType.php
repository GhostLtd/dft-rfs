<?php

namespace App\Form\DomesticSurvey\DayStop;

use App\Entity\Distance;
use App\Entity\Domestic\DayStop;
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

        $translationKeyPrefix = "domestic.day-stop.distance-travelled";
        $builder
            ->add('distanceTravelled', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled.label",
                'label_attr' => ['class' => 'govuk-label--xl'],
                'label_is_page_heading' => true,
                'help' => "{$translationKeyPrefix}.distance-travelled.help",
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
            'data_class' => DayStop::class,
        ]);
    }
}
