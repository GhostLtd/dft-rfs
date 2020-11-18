<?php

namespace App\Form\InternationalPreEnquiry;

use App\Entity\InternationalPreEnquiryResponse;
use App\Form\AddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CorrespondenceAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('correspondenceAddress', AddressType::class, [
                'label' => 'survey.pre-enquiry.correspondence-address.address.label',
                'label_is_page_heading' => true,
                'label_attr' => [
                    'class' => 'govuk-label--xl',
                ],
                'help' => 'survey.pre-enquiry.correspondence-address.address.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InternationalPreEnquiryResponse::class,
            'validation_groups' => ['correspondence_address'],
        ]);
    }
}
