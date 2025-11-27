<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use App\Form\Admin\InternationalSurvey\DataMapper\BusinessDetailsDataMapper;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper(new BusinessDetailsDataMapper())
            ->add('activityStatus', Gds\ChoiceType::class, [
                'label' => 'Does the firm still perform international road haulage activities?',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => SurveyResponse::ACTIVITY_STATUS_CHOICES,
                'choice_options' => [
                    'international.survey-response.activity-status.choices.still-active' => [
                        'conditional_form_name' => 'conditionalFields',
                    ],
                    'international.survey-response.activity-status.choices.no-road-haulage' => [
                        'conditional_form_name' => 'activityStatusOther'
                    ],
                ],
            ])
            ->add('activityStatusOther', Gds\InputType::class, [
                'label' => 'international.survey-response.activity-status.other.label',
                'help' => 'international.survey-response.activity-status.other.help',
                'attr' => ['class' => 'govuk-input--width-30'],
            ])
            ->add('conditionalFields', Gds\FieldsetType::class, [
                'label' => false,
            ]);

        if ($options['include_buttons']) {
            $builder
                ->add('buttons', Gds\ButtonGroupType::class)
                ->get('buttons')
                ->add('submit', Gds\ButtonType::class, [
                    'label' => 'Save changes',
                ])
                ->add('cancel', Gds\ButtonType::class, [
                    'label' => 'Cancel',
                    'attr' => ['class' => 'govuk-button--secondary'],
                ]);
        }

        $builder->get('conditionalFields')
            ->add('businessNature', Gds\InputType::class, [
                'label' => "Nature of business",
                'attr' => ['class' => 'govuk-input--width-30'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('numberOfEmployees', Gds\ChoiceType::class, [
                'choices' => AbstractSurveyResponse::EMPLOYEES_CHOICES,
                'placeholder' => '',
                'label' => "Number of employees nationally",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-select--width-15'],
                'expanded' => false,
            ])
            ->add('annualInternationalHGVJourneyCount', Gds\IntegerType::class, [
                'label' => 'international.survey-response.number-of-trips.admin-hgv-count.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-5',
                ],
            ])
            ->add('annualInternationalLGVJourneyCount', Gds\IntegerType::class, [
                'label' => 'international.survey-response.number-of-trips.admin-lgv-count.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-5',
                ],
            ]);

    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => function(FormInterface $form) {
                /** @var SurveyResponse $data */
                $data = $form->getData();

                $groups = ['admin_business_details'];

                if ($data->getActivityStatus() === SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE) {
                    $groups[] = 'number_of_trips';
                }

                return $groups;
            },
            'include_buttons' => true,
        ]);
    }
}
