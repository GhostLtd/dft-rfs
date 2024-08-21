<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\NotificationInterception;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationInterceptionType extends AbstractType
{
    public const EDIT_ALL = 'all';
    public const EDIT_ADDRESS = 'address';
    public const EDIT_EMAILS = 'emails';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (in_array($options['edit_mode'], [self::EDIT_ALL, self::EDIT_ADDRESS]))
        {
            $builder
                ->add('addressLine', Gds\InputType::class, [
                    'label' => "notification-interception.form.business-name.label",
                    'help' => "notification-interception.form.business-name.help",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'attr' => ['class' => 'govuk-input--width-30']
                ]);
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
            'validation_groups' => ['notification_interception'],
            'translation_domain' => 'admin',
            'edit_mode' => self::EDIT_ALL,
        ]);

        $resolver->setAllowedValues('edit_mode', [
            self::EDIT_ALL,
            self::EDIT_ADDRESS,
            self::EDIT_EMAILS,
        ]);
    }
}
