<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\Survey;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfirmCompleteActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = $options['translation_key_prefix'];

        $builder
            ->add('reasonForUnfilledSurvey', Gds\ChoiceType::class, [
                'choices' => Survey::UNFILLED_SURVEY_REASON_CHOICES,
                'choice_translation_domain' => 'messages',
                'label_attr' => ['class' => 'govuk-label--s'],
                'constraints' => [new NotBlank(['message' => 'Choose a reason'])],
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
            'confirm_button_options' => [],
        ]);
        $resolver->setRequired([
            'translation_key_prefix',
        ]);
    }
}