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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper(new BusinessDetailsDataMapper())
            ->add('activityStatus', Gds\ChoiceType::class, [
                'label' => 'Does the firm still perform international road haulage activities?',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => SurveyResponse::ACTIVITY_STATUS_CHOICES,
                'choice_options' => [
                    SurveyResponse::ACTIVITY_STATUS_CHOICES_PREFIX.SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE => [
                        'conditional_form_name' => 'conditionalFields',
                    ],
                ],
            ])
            ->add('conditionalFields', Gds\FieldsetType::class, [
                'label' => false,
            ]);

        if ($options['include_buttons']) {
            $builder
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
            ->add('annualInternationalJourneyCount', Gds\NumberType::class, [
                'label' => 'Estimated number of international trips that will be made by the firm in the next 12 months',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => [
                    'class' => 'govuk-input--width-5',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => function(FormInterface $form) {
                /** @var SurveyResponse $data */
                $data = $form->getData();

                return ($data && $data->getActivityStatus() === SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE) ?
                    ['admin_business_details'] : ['Default'];
            },
            'include_buttons' => true,
        ]);
    }
}