<?php

namespace App\Form\DomesticSurvey\DayStop;

use App\Entity\Distance;
use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\AbstractDistanceTravelledType;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DistanceTravelledType extends AbstractDistanceTravelledType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.day-stop.distance-travelled";
        $builder
            ->add('distanceTravelled', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled.label",
                'label_attr' => ['class' => $options['is_child_form'] ? 'govuk-label--s': 'govuk-label--xl'],
                'label_is_page_heading' => !$options['is_child_form'],
                'help' => "{$translationKeyPrefix}.distance-travelled.help",
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
            'data_class' => DayStop::class,
            'validation_groups' => 'day-stop.distance-travelled',
            'is_child_form' => false,
        ]);
    }
}
