<?php

namespace App\Form\Admin\LCNI;

use App\Entity\NotificationInterception;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationInterceptionType extends AbstractType
{
    public const string EDIT_ALL = 'all';
    public const string EDIT_EMAILS = 'emails';
    public const string EDIT_NAMES = 'names';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (in_array($options['edit_mode'], [self::EDIT_ALL, self::EDIT_NAMES])) {
            $builder
                ->add('names', Gds\TextareaType::class, [
                    'label' => "notification-interception.form.names.label",
                    'help' => "notification-interception.form.names.help",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'attr' => ['class' => 'govuk-input--width-30']
                ]);

            $builder->get('names')->addModelTransformer(new NotificationInterceptionCompanyNameDataTransformer());
        }

        if (in_array($options['edit_mode'], [self::EDIT_ALL, self::EDIT_EMAILS])) {
            $builder
                ->add('csrgtEmails', Gds\InputType::class, [
                    'label' => "notification-interception.form.csrgt-emails.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                ])
                ->add('irhsEmails', Gds\InputType::class, [
                    'label' => "notification-interception.form.irhs-emails.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                ])
                ->add('dvlaEmails', Gds\InputType::class, [
                    'label' => "notification-interception.form.dvla-email.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'disabled' => true,
                ])
                ;
        }

        $builder->add('buttons', Gds\ButtonGroupType::class, []);

        $builder->get('buttons')
            ->add('buttons', Gds\ButtonGroupType::class)
            ->get('buttons')
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "notification-interception.form.submit",
            ])
            ->add('cancel', Gds\LinkType::class, [
                'label' => "notification-interception.form.cancel",
                'href' => $options['cancel_url'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NotificationInterception::class,
            'translation_domain' => 'admin',
            'validation_groups' => function(FormInterface $form){
                    return match($form->getConfig()->getOption('edit_mode')) {
                        self::EDIT_ALL => ['notification_interception_names', 'notification_interception_emails'],
                        self::EDIT_EMAILS => ['notification_interception_emails'],
                        self::EDIT_NAMES => ['notification_interception_names'],
                        default => throw new \InvalidArgumentException('Unexpected edit_mode option'),
                    };
                },
            'edit_mode' => self::EDIT_ALL,
        ])->setRequired(['edit_mode', 'cancel_url']);
        ;


        $resolver->setAllowedValues('edit_mode', [
            self::EDIT_ALL,
            self::EDIT_EMAILS,
            self::EDIT_NAMES,
        ]);
    }
}
