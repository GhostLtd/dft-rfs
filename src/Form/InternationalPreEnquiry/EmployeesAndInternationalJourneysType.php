<?php

namespace App\Form\InternationalPreEnquiry;

use App\Entity\InternationalPreEnquiryResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeesAndInternationalJourneysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('employeeCount', Gds\NumberType::class, [
                'label' => 'survey.pre-enquiry.employees-and-international-journeys.employee-count.label',
                'help' => 'survey.pre-enquiry.employees-and-international-journeys.employee-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
            ])
            ->add('annualJourneyEstimate', Gds\NumberType::class, [
                'label' => 'survey.pre-enquiry.employees-and-international-journeys.annual-journey-estimate.label',
                'help' => 'survey.pre-enquiry.employees-and-international-journeys.annual-journey-estimate.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InternationalPreEnquiryResponse::class,
            'validation_groups' => ['employees_and_international_journeys'],
        ]);
    }
}
