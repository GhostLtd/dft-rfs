<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\Survey;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class
IntroductionType extends AbstractType
{
    const ACTIVE_CHOICE_YES = 'common.choices.boolean.yes';
    const ACTIVE_CHOICE_NO = 'common.choices.boolean.no';
    const ACTIVATE_CHOICES = [
        self::ACTIVE_CHOICE_YES => true,
        self::ACTIVE_CHOICE_NO => false,
    ];

    const DATA_ENTRY_MANUAL = 'roro.survey.data-entry.choices.manual';
    const DATA_ENTRY_ADVANCED = 'roro.survey.data-entry.choices.advanced';

    const DATA_ENTRY_MANUAL_CHOICE = 'manual';
    const DATA_ENTRY_ADVANCED_CHOICE = 'advanced';
    const DATA_ENTRY_CHOICES = [
        self::DATA_ENTRY_MANUAL => self::DATA_ENTRY_MANUAL_CHOICE,
        self::DATA_ENTRY_ADVANCED => self::DATA_ENTRY_ADVANCED_CHOICE,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $showDataEntryQuestion = $options['show_data_entry_question'];
        $choiceOptions = [];

        if ($showDataEntryQuestion) {
            $choiceOptions = [
                self::ACTIVE_CHOICE_YES => [
                    'conditional_form_name' => 'dataEntryMethod',
                ],
            ];
        }

        $builder
            ->add('isActiveForPeriod', ChoiceType::class, [
                'label_attr' => ['class' => $showDataEntryQuestion ? 'govuk-label--l' : 'govuk-label--m'],
                'label' => 'roro.survey.introduction.is-active',
                'choices' => self::ACTIVATE_CHOICES,
                'choice_options' => $choiceOptions,
            ]);

        if ($showDataEntryQuestion) {
            $builder
                ->add('dataEntryMethod', ChoiceType::class, [
                    'label_attr' => ['class' => 'govuk-label--m'],
                    'label' => 'roro.survey.introduction.data-entry-method.label',
                    'help' => 'roro.survey.introduction.data-entry-method.help',
                    'choices' => self::DATA_ENTRY_CHOICES,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'validation_groups' => ['roro.is-active'],
            'show_data_entry_question' => true,
        ]);
    }
}