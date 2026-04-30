<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\International\Survey;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class FinalDetailsType extends AbstractType implements DataMapperInterface
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                /** @var Survey $survey */
                $survey = $event->getData();
                $form = $event->getForm();

                if ($survey->shouldAskWhyEmptySurvey()) {
                    $translationKeyPrefix = 'international.survey-response.reason-for-empty-survey';
                    $form
                        ->add('reasonForEmptySurvey', Gds\ChoiceType::class, [
                            'choices' => Survey::REASON_FOR_EMPTY_SURVEY_CHOICES,
                            'label' => "{$translationKeyPrefix}.reason-for-empty-survey.label",
                            'label_attr' => ['class' => 'govuk-label--s'],
                            'translation_domain' => 'messages',
                            'choice_options' => [
                                Survey::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX.Survey::REASON_FOR_EMPTY_SURVEY_OTHER => [
                                    'conditional_form_name' => 'reasonForEmptySurveyOther',
                                ],
                            ],
                        ])
                        ->add('reasonForEmptySurveyOther', Gds\InputType::class, [
                            'label' => "{$translationKeyPrefix}.reason-for-empty-survey-other.label",
                            'help' => "{$translationKeyPrefix}.reason-for-empty-survey-other.help",
                            'label_attr' => ['class' => 'govuk-label--s'],
                            'translation_domain' => 'messages',
                        ])
                    ;
                }

                $form->add('button_group', Gds\ButtonGroupType::class);
                $form->get('button_group')
                    ->add('confirm', Gds\ButtonType::class, [
                        'type' => 'submit',
                        'label' => new TranslatableMessage('actions.confirm', [], 'messages'),
                    ])
                    ->add('cancel', Gds\LinkType::class, array_merge(
                        $options['cancel_link_options'],
                        ['label' => new TranslatableMessage('actions.cancel', [], 'messages')]
                    ));
            });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
        ]);

        $resolver->setRequired('cancel_link_options');
        $resolver->setAllowedTypes('cancel_link_options', ['array']);
        // Bundle's AbstractConfirmAction passes confirm_button_options too —
        // accept (and ignore) it; this form builds its own button.
        $resolver->setDefault('confirm_button_options', []);
        $resolver->setAllowedTypes('confirm_button_options', ['array']);

        $resolver->setDefault('validation_groups', function(FormInterface $form) {
            /** @var Survey $survey */
            $survey = $form->getData();

            return $survey->shouldAskWhyEmptySurvey() ? ['admin_reason_for_empty_survey'] : [];
        });
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Survey) {
            throw new Exception\UnexpectedTypeException($viewData, Survey::class);
        }

        $forms = iterator_to_array($forms);

        if ($forms['reasonForEmptySurvey'] ?? false) {
            $forms['reasonForEmptySurvey']->setData($viewData->getReasonForEmptySurvey());
            $forms['reasonForEmptySurveyOther']->setData($viewData->getReasonForEmptySurveyOther());
        }
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!$viewData instanceof Survey) {
            throw new Exception\UnexpectedTypeException($viewData, Survey::class);
        }

        if ($forms['reasonForEmptySurvey'] ?? false) {
            $viewData->setReasonForEmptySurvey($forms['reasonForEmptySurvey']->getData());
            $viewData->setReasonForEmptySurveyOther(
                $forms['reasonForEmptySurvey']->getData() === Survey::REASON_FOR_EMPTY_SURVEY_OTHER
                    ? $forms['reasonForEmptySurveyOther']->getData()
                    : null
            );
        }
    }
}
