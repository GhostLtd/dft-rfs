<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\Survey;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfirmApprovedActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = $options['translation_key_prefix'];

        $builder
            ->add('reasonForUnfilledSurvey', Gds\ChoiceType::class, [
                'choices' => Survey::UNFILLED_SURVEY_REASON_CHOICES,
                'choice_translation_domain' => 'messages',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('confirm', Gds\ButtonType::class, array_merge($options['confirm_button_options'], [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.confirm.label",
            ]))
            ->add('cancel', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.cancel.label",
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'confirm_button_options' => [],
            'validation_groups' => ['admin_reason_for_unfilled_survey'],
        ]);

        $resolver->setRequired([
            'translation_key_prefix',
        ]);
    }
}