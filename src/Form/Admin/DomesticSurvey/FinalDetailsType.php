<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Embeddable\Volume;
use App\Form\DomesticSurvey\ClosingDetails\VehicleFuelType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Ghost\GovUkFrontendBundle\Form\Type\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FinalDetailsType extends AbstractType
{
    protected bool $isMissingDays;

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                /** @var SurveyResponse $response */
                $response = $event->getData();
                $form = $event->getForm();

                $this->isMissingDays = $response->getDays()->count() !== 7;

                if ($response->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_YES) {
                    if ($response->hasJourneys()) {
                        $form->add('fuel', VehicleFuelType::class, [
                            'label' => false,
                            'property_path' => 'vehicle.response',
                        ]);
                    } else {
                        $translationKeyPrefix = 'domestic.survey-response.reason-for-empty-survey';
                        $form
                            ->add('reasonForEmptySurvey', Gds\ChoiceType::class, [
                                'choices' => SurveyResponse::EMPTY_SURVEY_REASON_CHOICES,
                                'label' => "{$translationKeyPrefix}.reason-for-empty-survey.label",
                                'label_attr' => ['class' => 'govuk-label--s'],
                                'expanded' => true,
                                'choice_options' => [
                                    SurveyResponse::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX.SurveyResponse::REASON_OTHER => [
                                        'conditional_form_name' => 'reasonForEmptySurveyOther',
                                    ]
                                ],
                            ])
                            ->add('reasonForEmptySurveyOther', Gds\InputType::class, [
                                'label' => "{$translationKeyPrefix}.other.help",
                                'label_attr' => ['class' => 'govuk-label--s'],
                            ]);
                    }
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'submit_name' => 'submit',
            'submit_label' => 'Save changes',
            'label_translation_domain' => null,
            'error_mapping' => [
                'vehicle' => 'fuel',
            ],
        ]);

        $resolver->setDefault('validation_groups', function(FormInterface $form) {
            /** @var SurveyResponse $response */
            $response = $form->getData();

            if (!$response->getIsInPossessionOfVehicle()) {
                return [];
            }

            return $response->hasJourneys() ? ['admin_vehicle_fuel'] : ['admin_reason_for_empty_survey'];
        });
    }
}
