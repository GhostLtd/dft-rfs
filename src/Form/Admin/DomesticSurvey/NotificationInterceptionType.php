<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\NotificationInterception;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationInterceptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('addressLine', Gds\InputType::class, [
                'label' => "notification-interception.form.business-name.label",
                'help' => "notification-interception.form.business-name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-30']
            ])
            ->add('emails', Gds\InputType::class, [
                'label' => "notification-interception.form.emails.label",
                'help' => "notification-interception.form.emails.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])

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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NotificationInterception::class,
            'validation_groups' => ['notification_interception'],
            'translation_domain' => 'admin',
        ]);
    }
}