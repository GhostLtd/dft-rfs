<?php

namespace App\Form\PreEnquiry;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SubmitPreEnquiryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('submit', ButtonType::class, [
                'label' => 'pre-enquiry.summary.submit.label',
            ]);
    }

}