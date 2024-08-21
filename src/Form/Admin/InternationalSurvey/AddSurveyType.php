<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\International\Survey;
use App\Form\LongAddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class AddSurveyType extends AbstractType implements DataTransformerInterface
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "admin.international.survey";

        $builder
            ->addModelTransformer($this)
            ->add('company', CompanyType::class, [
                'label' => false,
                'disabled' => $options['is_resend'],
            ])
            ->add('referenceNumber', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.reference-number.label",
                'help' => "{$translationKeyPrefix}.reference-number.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'disabled' => $options['is_resend'],
            ])
            ->add('surveyPeriodStart', Gds\DateType::class, [
                'label' => "{$translationKeyPrefix}.period-start.label",
                'help' => "{$translationKeyPrefix}.period-start.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'disabled' => $options['is_resend'],
            ])
            ->add('surveyPeriodEnd', Gds\DateType::class, [
                'label' => "{$translationKeyPrefix}.period-end.label",
                'help' => "{$translationKeyPrefix}.period-end.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'disabled' => $options['is_resend'],
            ])
            ->add('invitationEmails', Gds\EmailType::class, [
                'label' => "{$translationKeyPrefix}.invitation-email.label",
                'help' => "{$translationKeyPrefix}.invitation-email.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('invitationAddress', LongAddressType::class, [
                'label' => "{$translationKeyPrefix}.invitation-address.label",
                'help' => "{$translationKeyPrefix}.invitation-address.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'include_addressee' => false,
            ])
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => $options['is_resend'] ?
                        "{$translationKeyPrefix}.submit.resend-label" :
                        "{$translationKeyPrefix}.submit.label",
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "common.actions.cancel",
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'validation_groups' => ['add_survey', 'notify_api', 'add_international_survey'],
            'is_resend' => false,
        ]);
    }

    #[\Override]
    public function transform($value): mixed
    {
        return $value;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if ($value instanceof Survey) {
            $businessName = $value->getCompany()->getBusinessName();
            $invitationAddress = $value->getInvitationAddress();

            if ($businessName && $invitationAddress && $invitationAddress->isFilled()) {
                $invitationAddress->setLine1($businessName);
            }
        }

        return $value;
    }
}
