<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\Survey;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntroductionType extends AbstractType
{
    public const DATA_ENTRY_MANUAL = 'roro.survey.data-entry.choices.manual';
    public const DATA_ENTRY_ADVANCED = 'roro.survey.data-entry.choices.advanced';

    public const DATA_ENTRY_MANUAL_CHOICE = 'manual';
    public const DATA_ENTRY_ADVANCED_CHOICE = 'advanced';
    public const DATA_ENTRY_CHOICES = [
        self::DATA_ENTRY_MANUAL => self::DATA_ENTRY_MANUAL_CHOICE,
        self::DATA_ENTRY_ADVANCED => self::DATA_ENTRY_ADVANCED_CHOICE,
    ];

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $showDataEntryQuestion = $options['show_data_entry_question'];
        $choiceOptions = [];

        if ($showDataEntryQuestion) {
            $choiceOptions = [
                'boolean.true' => [
                    'conditional_form_name' => 'dataEntryMethod',
                ],
            ];
        }

        $builder
            ->add('isActiveForPeriod', BooleanChoiceType::class, [
                'label_attr' => ['class' => $showDataEntryQuestion ? 'govuk-fieldset__legend--l' : 'govuk-fieldset__legend--s'],
                'label' => 'roro.survey.introduction.is-active',
                'choice_options' => $choiceOptions,
            ]);

        if ($showDataEntryQuestion) {
            $builder
                ->add('dataEntryMethod', ChoiceType::class, [
                    'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
                    'label' => 'roro.survey.introduction.data-entry-method.label',
                    'help' => 'roro.survey.introduction.data-entry-method.help',
                    'choices' => self::DATA_ENTRY_CHOICES,
                ]);
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'show_data_entry_question' => true,
        ]);

        $resolver->setDefault('validation_groups', function(Options $options): array {
            $groups = ['roro.is-active'];

            if ($options['show_data_entry_question']) {
                $groups[] = 'roro.data-entry-method';
            }

            return $groups;
        });
    }
}
