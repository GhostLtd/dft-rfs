<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Form\AddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CorrespondenceAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contactAddress', AddressType::class, [
                'label' => 'pre-enquiry.correspondence-address.address.label',
                'label_attr' => [
                    'class' => 'govuk-label--m',
                ],
                'help' => 'pre-enquiry.correspondence-address.address.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PreEnquiryResponse::class,
            'validation_groups' => ['correspondence_address'],
        ]);
    }
}
