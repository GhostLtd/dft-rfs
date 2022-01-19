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
        $translationKeyPrefix = "admin.domestic.notification-interception.form";

        $builder
            ->add('addressLine', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.address-line.label",
                'help' => "{$translationKeyPrefix}.address-line.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-30']
            ])
            ->add('email', Gds\EmailType::class, [
                'label' => "{$translationKeyPrefix}.email.label",
                'help' => "{$translationKeyPrefix}.email.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])

            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.submit",
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.cancel",
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NotificationInterception::class,
            'validation_groups' => ['notification_interception'],
        ]);
    }
}