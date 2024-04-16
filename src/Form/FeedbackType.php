<?php

namespace App\Form;

use App\Entity\Feedback;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackType extends AbstractType
{
    public const ISSUES_UNSOLVED = 'yes-unsolved';

    public const EXPERIENCE_CHOICES = ['5-very-good', '4-good', '3-neutral', '2-bad', '1-very-bad'];
    public const COMPARISON_CHOICES = ['5-much-better', '4-better', '3-same', '2-worse', '1-much-worse'];
    public const TIME_CHOICES = ['5-less-time', '3-same', '1-more-time'];
    public const ISSUES_CHOICES = ['no', 'yes-solved', self::ISSUES_UNSOLVED];

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setDataMapper(new FeedbackTypeDataMapper());

        $builder
            ->add('experienceRating', ChoiceType::class, [
                'label' => 'survey-feedback.experience-rating.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => $this->getChoices("experience-rating", self::EXPERIENCE_CHOICES),
            ])
            ->add('hasCompletedPaperSurvey', BooleanChoiceType::class, [
                'label' => 'survey-feedback.has-completed-paper-survey.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choice_options' => [
                    'boolean.true' => [
                        'conditional_form_name' => 'paper_survey_group',
                    ]
                ],
            ])
            ->add('paper_survey_group', FormType::class, [
                'inherit_data' => true,
                'label' => false,
            ])
            ->add('hadIssues', ChoiceType::class, [
                'label' => 'survey-feedback.had-issues.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => $this->getChoices('had-issues', self::ISSUES_CHOICES),
                'choice_options' => [
                    "survey-feedback.had-issues.choices.yes-unsolved" => ['conditional_form_name' => 'issueDetails'],
                ],
            ])
            ->add('issueDetails', TextareaType::class, [
                'label' => 'survey-feedback.issue-details.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'survey-feedback.comments.label',
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
        $builder->get('paper_survey_group')
            ->add('comparisonRating', ChoiceType::class, [
                'label' => 'survey-feedback.comparison-rating.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => $this->getChoices("comparison-rating", self::COMPARISON_CHOICES),
            ])
            ->add('timeToComplete', ChoiceType::class, [
                'label' => 'survey-feedback.time-to-complete.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => $this->getChoices("time-to-complete", self::TIME_CHOICES),
            ]);

        $builder
            ->add('confirm', ButtonType::class, [
                'type' => 'submit',
                'label' => "survey-feedback.confirm.label",
            ])
            ->add('cancel', ButtonType::class, [
                'type' => 'submit',
                'label' => "survey-feedback.cancel.label",
                'attr' => ['class' => 'govuk-button--secondary'],
            ])
        ;
    }

    private function getChoices(string $translationSegment, array $input): array
    {
        return array_combine(
            array_map(fn($a) => "survey-feedback.$translationSegment.choices.$a", $input),
            $input
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
        ]);
    }
}
