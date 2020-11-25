<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberOfTripsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('annualInternationalJourneyCount', Gds\NumberType::class, [
                'label' => 'international.survey-response.number-of-trips.annual-international-journey-count.label',
                'help' => 'international.survey-response.number-of-trips.annual-international-journey-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['number_of_trips'],
        ]);
    }
}
