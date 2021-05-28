<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CorrespondenceDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('correspondenceName', Gds\InputType::class, [
                'label' => 'pre-enquiry.correspondence-details.name.label',
                'help' => 'pre-enquiry.correspondence-details.name.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('phone', Gds\InputType::class, [
                'label' => 'pre-enquiry.correspondence-details.phone.label',
                'help' => 'pre-enquiry.correspondence-details.phone.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('email', Gds\EmailType::class, [
                'label' => 'pre-enquiry.correspondence-details.email.label',
                'help' => 'pre-enquiry.correspondence-details.email.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PreEnquiryResponse::class,
            'validation_groups' => ['correspondence_details'],
        ]);
    }
}
