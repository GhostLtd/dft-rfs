<?php

namespace App\Form\InternationalPreEnquiry;

use App\Entity\InternationalPreEnquiryResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyNameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('companyName', Gds\InputType::class, [
                'label' => 'survey.pre-enquiry.business-details.company-name.label',
                'label_is_page_heading' => true,
                'label_attr' => [
                    'class' => 'govuk-label--xl',
                ],
                'help' => 'survey.pre-enquiry.business-details.company-name.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InternationalPreEnquiryResponse::class,
            'validation_groups' => ['company_name'],
        ]);
    }
}
