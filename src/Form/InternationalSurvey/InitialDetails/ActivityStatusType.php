<?php

namespace App\Form\InternationalSurvey\InitialDetails;

use App\Entity\International\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityStatusType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('activityStatus', Gds\ChoiceType::class, [
                'label' => 'international.survey-response.activity-status.activity-status.label',
                'help' => 'international.survey-response.activity-status.activity-status.help',
                'choices' => SurveyResponse::ACTIVITY_STATUS_CHOICES,
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--l'],
                'choice_options' => fn(string $x) => match($x) {
                    'no-road-haulage' => ['conditional_form_name' => 'activityStatusOther'],
                    default => [],
                },
            ])
            ->add('activityStatusOther', Gds\InputType::class, [
                'label' => 'international.survey-response.activity-status.other.label',
                'help' => 'international.survey-response.activity-status.other.help',
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['activity_status'],
        ]);
    }
}
