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
        $builder
            ->add('numberOfStopsLoadingAndUnloading', Gds\NumberType::class, [
                'label' => 'survey.domestic.forms.day-summary.number-of-stops-both.label',
                'help' => 'survey.domestic.forms.day-summary.number-of-stops-both.help',
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->add('numberOfStopsLoading', Gds\NumberType::class, [
                'label' => 'survey.domestic.forms.day-summary.number-of-stops-loading.label',
                'help' => 'survey.domestic.forms.day-summary.number-of-stops-loading.help',
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->add('numberOfStopsUnloading', Gds\NumberType::class, [
                'label' => 'survey.domestic.forms.day-summary.number-of-stops-unloading.label',
                'help' => 'survey.domestic.forms.day-summary.number-of-stops-unloading.help',
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
        ]);
    }
}
