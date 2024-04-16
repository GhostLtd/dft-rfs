<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\DataMapper\NumberOfTripsDataMapper;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberOfTripsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('annualInternationalJourneyCount', Gds\IntegerType::class, [
                'label' => 'international.survey-response.number-of-trips.annual-international-journey-count.label',
                'help' => 'international.survey-response.number-of-trips.annual-international-journey-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-5',
                ],
            ])
            ->setDataMapper(new NumberOfTripsDataMapper())
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['number_of_trips'],
        ]);
    }
}
