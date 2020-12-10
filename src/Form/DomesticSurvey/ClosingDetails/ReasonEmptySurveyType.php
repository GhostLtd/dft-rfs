<?php

namespace App\Form\DomesticSurvey\ClosingDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Form\WorkflowChoiceFormInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReasonEmptySurveyType extends AbstractType implements WorkflowChoiceFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.survey-response.reason-for-empty-survey";
        $builder
            ->add('reasonForEmptySurvey', Gds\ChoiceType::class, [
                'choices' => SurveyResponse::EMPTY_SURVEY_REASON_CHOICES,
                'label' => "{$translationKeyPrefix}.reason-for-empty-survey.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--l'],
                'help' => "{$translationKeyPrefix}.reason-for-empty-survey.help",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => 'reason_for_empty_survey'
        ]);
    }
}
