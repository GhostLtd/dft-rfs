<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeesAndInternationalJourneysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationPrefix = "pre-enquiry.employees-and-international-journeys";
        $builder
            ->add('numberOfEmployees', Gds\ChoiceType::class, [
                'choices' => AbstractSurveyResponse::EMPLOYEES_CHOICES,
                'label' => "{$translationPrefix}.number-of-employees.label",
                'help' => "{$translationPrefix}.number-of-employees.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-select--width-15'],
                'expanded' => true,
            ])
            ->add('annualJourneyEstimate', Gds\NumberType::class, [
                'label' => "{$translationPrefix}.annual-journey-estimate.label",
                'help' => "{$translationPrefix}.annual-journey-estimate.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PreEnquiryResponse::class,
            'validation_groups' => ['employees_and_international_journeys'],
        ]);
    }
}
