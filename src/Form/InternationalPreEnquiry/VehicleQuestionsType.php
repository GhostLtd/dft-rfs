<?php

namespace App\Form\InternationalPreEnquiry;

use App\Entity\International\PreEnquiryResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleQuestionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('totalVehicleCount', Gds\NumberType::class, [
                'label' => 'survey.pre-enquiry.vehicle-questions.total-vehicle-count.label',
                'help' => 'survey.pre-enquiry.vehicle-questions.total-vehicle-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
            ])
            ->add('internationalJourneyVehicleCount', Gds\NumberType::class, [
                'label' => 'survey.pre-enquiry.vehicle-questions.international-journey-vehicle-count.label',
                'help' => 'survey.pre-enquiry.vehicle-questions.international-journey-vehicle-count.help',
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
            'validation_groups' => ['vehicle_questions'],
        ]);
    }
}
