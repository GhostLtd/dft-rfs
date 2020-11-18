<?php

namespace App\Form\InternationalPreEnquiry;

use App\Entity\InternationalPreEnquiryResponse;
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
                'label' => 'survey.pre-enquiry.correspondence-details.name.label',
                'help' => 'survey.pre-enquiry.correspondence-details.name.help',
            ])
            ->add('phone', Gds\InputType::class, [
                'label' => 'survey.pre-enquiry.correspondence-details.phone.label',
                'help' => 'survey.pre-enquiry.correspondence-details.phone.help',
            ])
            ->add('email', Gds\EmailType::class, [
                'label' => 'survey.pre-enquiry.correspondence-details.email.label',
                'help' => 'survey.pre-enquiry.correspondence-details.email.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InternationalPreEnquiryResponse::class,
            'validation_groups' => ['correspondence_details'],
        ]);
    }
}
