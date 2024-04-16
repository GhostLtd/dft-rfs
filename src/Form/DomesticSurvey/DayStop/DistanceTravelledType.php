<?php

namespace App\Form\DomesticSurvey\DayStop;

use App\Entity\Distance;
use App\Entity\Domestic\DayStop;
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
        $translationKeyPrefix = "domestic.day-stop.distance-travelled";

        $labelIsPageHeading = !$options['is_child_form'];

        $builder
            ->add('distanceTravelled', ValueUnitType::class, [
                'label' => "{$translationKeyPrefix}.distance-travelled.label",
                'label_attr' => ['class' => $labelIsPageHeading ? 'govuk-fieldset__legend--xl': 'govuk-fieldset__legend--s'],
                'label_is_page_heading' => $labelIsPageHeading,
                'help' => "{$translationKeyPrefix}.distance-travelled.help",
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
            'data_class' => DayStop::class,
            'validation_groups' => 'day-stop.distance-travelled',
            'is_child_form' => false,
        ]);
    }
}
