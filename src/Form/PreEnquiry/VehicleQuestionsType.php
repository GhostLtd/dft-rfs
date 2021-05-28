<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
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
                'label' => 'pre-enquiry.vehicle-questions.total-vehicle-count.label',
                'help' => 'pre-enquiry.vehicle-questions.total-vehicle-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('internationalJourneyVehicleCount', Gds\NumberType::class, [
                'label' => 'pre-enquiry.vehicle-questions.international-journey-vehicle-count.label',
                'help' => 'pre-enquiry.vehicle-questions.international-journey-vehicle-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
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
