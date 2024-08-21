<?php

namespace App\Form\InternationalSurvey\ClosingDetails;

use App\Entity\International\Survey;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReasonEmptySurveyType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "international.survey-response.reason-for-empty-survey";
        $builder
            ->add('reasonForEmptySurvey', Gds\ChoiceType::class, [
                'choices' => Survey::REASON_FOR_EMPTY_SURVEY_CHOICES,
                'label' => "{$translationKeyPrefix}.reason-for-empty-survey.label",
//                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "{$translationKeyPrefix}.reason-for-empty-survey.help",
                'choice_options' => [
                    Survey::REASON_FOR_EMPTY_SURVEY_CHOICES_PREFIX . Survey::REASON_FOR_EMPTY_SURVEY_OTHER => ['conditional_form_name' => 'reasonForEmptySurveyOther'],
                ],
            ])
            ->add('reasonForEmptySurveyOther', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.reason-for-empty-survey-other.label",
                'help' => "{$translationKeyPrefix}.reason-for-empty-survey-other.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'validation_groups' => 'reason_for_empty_survey'
        ]);
    }
}
