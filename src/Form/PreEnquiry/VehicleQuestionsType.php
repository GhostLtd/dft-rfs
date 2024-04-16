<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleQuestionsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('totalVehicleCount', Gds\IntegerType::class, [
                'label' => 'pre-enquiry.vehicle-questions.total-vehicle-count.label',
                'help' => 'pre-enquiry.vehicle-questions.total-vehicle-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('internationalJourneyVehicleCount', Gds\IntegerType::class, [
                'label' => 'pre-enquiry.vehicle-questions.international-journey-vehicle-count.label',
                'help' => 'pre-enquiry.vehicle-questions.international-journey-vehicle-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('annualJourneyEstimate', Gds\IntegerType::class, [
                'label' => "pre-enquiry.vehicle-questions.annual-journey-estimate.label",
                'help' => "pre-enquiry.vehicle-questions.annual-journey-estimate.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PreEnquiryResponse::class,
            'validation_groups' => ['vehicle_questions'],
        ]);
    }
}
