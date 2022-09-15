<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\Survey;
use App\Form\LongAddressType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddSurveyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "admin.domestic.add-survey";

        $builder
            ->add('registrationMark', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.registration-mark.label",
                'help' => "{$translationKeyPrefix}.registration-mark.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'disabled' => $options['is_reissue'] || $options['is_resend'],
            ])
            ->add('isNorthernIreland', Gds\ChoiceType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
                'label' => 'Survey region',
                'attr' => ['class' => 'govuk-radios--inline'],
                'choices' => [
                    'GB' => false,
                    'NI' => true,
                ],
                'disabled' => $options['is_reissue'] || $options['is_resend'],
            ])
            ->add('surveyPeriodStart', Gds\DateType::class, [
                'label' => "{$translationKeyPrefix}.start-date.label",
                'help' => "{$translationKeyPrefix}.start-date.help",
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
            ])
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => $options['is_resend'] ?
                    "{$translationKeyPrefix}.submit.resend-label" :
                    "{$translationKeyPrefix}.submit.label",
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'validation_groups' => ['add_survey', 'notify_api'],
            'is_reissue' => false,
            'is_resend' => false,
        ]);
    }
}