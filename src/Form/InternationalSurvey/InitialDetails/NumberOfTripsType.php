<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberOfTripsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $labelParameters = ['estimatePeriod' => $options['estimate_period']];

        $builder
            ->add('annualInternationalHGVJourneyCount', Gds\IntegerType::class, [
                'label' => 'international.survey-response.number-of-trips.hgv-count.label',
                'label_html' => 'markdown',
                'label_translation_parameters' => $labelParameters,
                'help' => 'international.survey-response.number-of-trips.hgv-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-5',
                ],
            ])
            ->add('annualInternationalLGVJourneyCount', Gds\IntegerType::class, [
                'label' => 'international.survey-response.number-of-trips.lgv-count.label',
                'label_html' => 'markdown',
                'label_translation_parameters' => $labelParameters,
                'help' => 'international.survey-response.number-of-trips.lgv-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-5',
                ],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['number_of_trips'],
            'estimate_period' => null,
        ]);
        $resolver->setAllowedTypes('estimate_period', ['null', 'string']);
    }
}
