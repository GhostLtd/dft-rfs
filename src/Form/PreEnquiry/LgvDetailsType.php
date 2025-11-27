<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LgvDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('totalLgvCount', Gds\IntegerType::class, [
                'label' => 'pre-enquiry.lgv-details.total-lgv-count.label',
                'help' => 'pre-enquiry.lgv-details.total-lgv-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('internationalJourneyLgvCount', Gds\IntegerType::class, [
                'label' => 'pre-enquiry.lgv-details.international-journey-lgv-count.label',
                'help' => 'pre-enquiry.lgv-details.international-journey-lgv-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('annualLgvJourneyEstimate', Gds\IntegerType::class, [
                'label' => "pre-enquiry.lgv-details.annual-lgv-journey-estimate.label",
                'help' => "pre-enquiry.lgv-details.annual-lgv-journey-estimate.help",
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
            'validation_groups' => ['lgv_details'],
        ]);
    }
}
