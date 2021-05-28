<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\Domestic\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FinalDetailsType extends AbstractType implements DataMapperInterface
{
    protected bool $isMissingDays;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                /** @var SurveyResponse $response */
                $response = $event->getData();
                $form = $event->getForm();

                if (!$response->isFilledOut()) {
                    $translationKeyPrefix = 'international.survey-response.reason-for-empty-survey';
                    $form
                        ->add('reasonForEmptySurvey', Gds\ChoiceType::class, [
                            'choices' => SurveyResponse::REASON_FOR_EMPTY_SURVEY_CHOICES,
                            'label' => "{$translationKeyPrefix}.reason-for-empty-survey.label",
                            'label_attr' => ['class' => 'govuk-label--s'],
                            'choice_options' => [
                                SurveyResponse::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX.SurveyResponse::REASON_FOR_EMPTY_SURVEY_OTHER => [
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
                    ->add('submit', Gds\ButtonType::class, [
                        'label' => 'Save changes',
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
            'data_class' => SurveyResponse::class,
        ]);

        $resolver->setDefault('validation_groups', function(FormInterface $form) {
            /** @var SurveyResponse $response */
            $response = $form->getData();

            return $response->isFilledOut() ? [] : ['admin_reason_for_empty_survey'];
        });
    }

    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof SurveyResponse) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
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
        if (!$viewData instanceof SurveyResponse) {
            throw new Exception\UnexpectedTypeException($viewData, Trip::class);
        }

        if ($forms['reasonForEmptySurvey'] ?? false) {
            $viewData->setReasonForEmptySurvey($forms['reasonForEmptySurvey']->getData());
            $viewData->setReasonForEmptySurveyOther(
                $forms['reasonForEmptySurvey']->getData() === SurveyResponse::REASON_FOR_EMPTY_SURVEY_OTHER
                    ? $forms['reasonForEmptySurveyOther']->getData()
                    : null
            );
        }
    }
}