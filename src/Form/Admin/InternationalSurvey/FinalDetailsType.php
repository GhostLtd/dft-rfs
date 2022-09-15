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

class FinalDetailsType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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
                        ])
                        ;
                }

                $form
                    ->add($options['submit_name'], Gds\ButtonType::class, [
                        'label' => $options['submit_label'],
                        'translation_domain' => $options['label_translation_domain']
                    ])
                    ->add('cancel', Gds\ButtonType::class, [
                        'label' => 'Cancel',
                        'attr' => ['class' => 'govuk-button--secondary'],
                    ]);
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'submit_name' => 'submit',
            'submit_label' => 'Save changes',
            'label_translation_domain' => null,
        ]);

        $resolver->setDefault('validation_groups', function(FormInterface $form) {
            /** @var Survey $survey */
            $survey = $form->getData();

            return $survey->shouldAskWhyEmptySurvey() ? ['admin_reason_for_empty_survey'] : [];
        });
    }

    public function mapDataToForms($viewData, $forms)
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

    public function mapFormsToData($forms, &$viewData)
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