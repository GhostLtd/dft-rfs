<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BusinessDetailsType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper($this)
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
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);

        $builder->get('conditionalFields')
            ->add('businessNature', Gds\InputType::class, [
                'label' => "Nature of business",
                'attr' => ['class' => 'govuk-input--width-30'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('numberOfEmployees', Gds\ChoiceType::class, [
                'choices' => AbstractSurveyResponse::EMPLOYEES_CHOICES,
                'placeholder' => '-- Please choose --',
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
            }
        ]);
    }

    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        // initialize form field values
        $forms['activityStatus']->setData($viewData->getActivityStatus());
        $forms['numberOfEmployees']->setData($viewData->getNumberOfEmployees());
        $forms['businessNature']->setData($viewData->getBusinessNature());
        $forms['annualInternationalJourneyCount']->setData($viewData->getAnnualInternationalJourneyCount());
    }

    public function mapFormsToData($forms, &$viewData)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        $viewData->setActivityStatus($forms['activityStatus']->getData());

        $activityStatus = $viewData->getActivityStatus();
        if (in_array($activityStatus, [SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING, SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK])) {
            $viewData
                ->setNumberOfEmployees(null)
                ->setBusinessNature(null)
                ->setAnnualInternationalJourneyCount(0);
        } else {
            $viewData
                ->setNumberOfEmployees($forms['numberOfEmployees']->getData())
                ->setBusinessNature($forms['businessNature']->getData())
                ->setAnnualInternationalJourneyCount($forms['annualInternationalJourneyCount']->getData());
        }
    }
}