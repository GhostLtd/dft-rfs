<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class NumberOfStopsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.day-summary.number-of-stops";
        $builder
            ->add('numberOfStopsLoading', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.number-of-stops-loading.label",
                'help' => "{$translationKeyPrefix}.number-of-stops-loading.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('numberOfStopsUnloading', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.number-of-stops-unloading.label",
                'help' => "{$translationKeyPrefix}.number-of-stops-unloading.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('numberOfStopsLoadingAndUnloading', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.number-of-stops-both.label",
                'help' => "{$translationKeyPrefix}.number-of-stops-both.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
            'validation_groups' => 'day-summary.number-of-stops',
        ]);
    }
}
