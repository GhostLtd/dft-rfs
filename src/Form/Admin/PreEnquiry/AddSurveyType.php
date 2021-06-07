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
        $translationKeyPrefix = "admin.pre-enquiry.add";

        $builder
            ->addModelTransformer($this)
            ->add('company', CompanyType::class, [
                'label' => false,
            ])
            ->add('referenceNumber', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.reference-number.label",
                'help' => "{$translationKeyPrefix}.reference-number.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('invitationEmail', Gds\EmailType::class, [
                'label' => "{$translationKeyPrefix}.invitation-email.label",
                'help' => "{$translationKeyPrefix}.invitation-email.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'disabled' => true,
            ])
            ->add('invitationAddress', LongAddressType::class, [
                'label' => "{$translationKeyPrefix}.invitation-address.label",
                'help' => "{$translationKeyPrefix}.invitation-address.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'include_addressee' => false,
            ])
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.submit.label",
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
        ]);
    }

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        if ($value instanceof PreEnquiry) {
            $businessName = $value->getCompany()->getBusinessName();
            $invitationAddress = $value->getInvitationAddress();

            if ($businessName && $invitationAddress && $invitationAddress->isFilled()) {
                $invitationAddress->setLine1($businessName);
            }
        }

        return $value;
    }
}