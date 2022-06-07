<?php

namespace App\Form\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Form\Admin\InternationalSurvey\CompanyType;
use App\Form\LongAddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class AddSurveyType extends AbstractType implements DataTransformerInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addModelTransformer($this)
            ->add('companyName', Gds\InputType::class, [
                'label' => "admin.pre-enquiry.add.company-name.label",
                'help' => "admin.pre-enquiry.add.company-name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
                'disabled' => $options['is_resend'],
            ])
            ->add('referenceNumber', Gds\InputType::class, [
                'label' => "admin.pre-enquiry.add.reference-number.label",
                'help' => "admin.pre-enquiry.add.reference-number.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'disabled' => $options['is_resend'],
            ])
            ->add('dispatchDate', Gds\DateType::class, [
                'label' => "admin.pre-enquiry.add.dispatch-date.label",
                'help' => "admin.pre-enquiry.add.dispatch-date.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'disabled' => $options['is_resend'],
                'data' => new \DateTime(),
            ])
            ->add('invitationEmails', Gds\EmailType::class, [
                'label' => "admin.pre-enquiry.add.invitation-email.label",
                'help' => "admin.pre-enquiry.add.invitation-email.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'disabled' => true,
            ])
            ->add('invitationAddress', LongAddressType::class, [
                'label' => "admin.pre-enquiry.add.invitation-address.label",
                'help' => "admin.pre-enquiry.add.invitation-address.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'include_addressee' => false,
            ])
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => $options['is_resend'] ?
                    "admin.pre-enquiry.add.submit.resend-label" :
                    "admin.pre-enquiry.add.submit.label",
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "common.actions.cancel",
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PreEnquiry::class,
            'validation_groups' => 'add_survey',
            'is_resend' => false,
        ]);
    }

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        if ($value instanceof PreEnquiry) {
            $businessName = $value->getCompanyName();
            $invitationAddress = $value->getInvitationAddress();

            if ($businessName && $invitationAddress && $invitationAddress->isFilled()) {
                $invitationAddress->setLine1($businessName);
            }
        }

        return $value;
    }
}