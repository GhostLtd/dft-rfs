<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HgvDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('totalHgvCount', Gds\IntegerType::class, [
                'label' => 'pre-enquiry.hgv-details.total-hgv-count.label',
                'help' => 'pre-enquiry.hgv-details.total-hgv-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('internationalJourneyHgvCount', Gds\IntegerType::class, [
                'label' => 'pre-enquiry.hgv-details.international-journey-hgv-count.label',
                'help' => 'pre-enquiry.hgv-details.international-journey-hgv-count.help',
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('annualHgvJourneyEstimate', Gds\IntegerType::class, [
                'label' => "pre-enquiry.hgv-details.annual-hgv-journey-estimate.label",
                'help' => "pre-enquiry.hgv-details.annual-hgv-journey-estimate.help",
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
            'validation_groups' => ['hgv_details'],
        ]);
    }
}
