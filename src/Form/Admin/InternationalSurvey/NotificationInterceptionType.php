<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\International\NotificationInterception;
use App\Form\Admin\InternationalSurvey\DataMapper\NotificationInterceptionCompanyNameDataTransformer;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationInterceptionType extends AbstractType
{
    public const EDIT_ALL = 'all';
    public const EDIT_EMAILS = 'emails';
    public const EDIT_NAMES = 'names';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (in_array($options['edit_mode'], [self::EDIT_ALL, self::EDIT_NAMES])) {
            $builder
                ->add('primaryName', Gds\InputType::class, [
                    'label' => "notification-interception.form.business-name.label",
                    'help' => "notification-interception.form.business-name.help",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'attr' => ['class' => 'govuk-input--width-30']
                ])
                ->add('additionalNames', Gds\TextareaType::class, [
                    'label' => "notification-interception.form.additional-names.label",
                    'help' => "notification-interception.form.additional-names.help",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'attr' => ['class' => 'govuk-input--width-30']
                ]);

            $builder->get('additionalNames')->addModelTransformer(new NotificationInterceptionCompanyNameDataTransformer());
        }

        if (in_array($options['edit_mode'], [self::EDIT_ALL, self::EDIT_EMAILS])) {
            $builder
                ->add('emails', Gds\InputType::class, [
                    'label' => "notification-interception.form.emails.label",
                    'help' => "notification-interception.form.emails.help",
                    'label_attr' => ['class' => 'govuk-label--s'],
                ]);
        }

        $builder
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "notification-interception.form.submit",
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "notification-interception.form.cancel",
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NotificationInterception::class,
            'translation_domain' => 'admin',
            'validation_groups' => ['notification_interception'],
            'edit_mode' => self::EDIT_ALL,
        ]);

        $resolver->setAllowedValues('edit_mode', [
            self::EDIT_ALL,
            self::EDIT_EMAILS,
            self::EDIT_NAMES,
        ]);
    }
}
