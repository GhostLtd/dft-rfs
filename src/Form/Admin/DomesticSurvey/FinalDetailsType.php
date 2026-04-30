<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\ClosingDetails\VehicleFuelType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

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
                                'translation_domain' => 'messages',
                                'choice_options' => [
                                    SurveyResponse::EMPTY_SURVEY_REASON_TRANSLATION_PREFIX.SurveyResponse::REASON_OTHER => [
                                        'conditional_form_name' => 'reasonForEmptySurveyOther',
                                    ]
                                ],
                            ])
                            ->add('reasonForEmptySurveyOther', Gds\InputType::class, [
                                'label' => "{$translationKeyPrefix}.other.help",
                                'label_attr' => ['class' => 'govuk-label--s'],
                                'translation_domain' => 'messages',
                            ]);
                    }
                }

                $form->add('button_group', Gds\ButtonGroupType::class);
                $form->get('button_group')
                    ->add('confirm', Gds\ButtonType::class, [
                        'type' => 'submit',
                        'label' => new TranslatableMessage('actions.confirm', [], 'messages'),
                    ])
                    ->add('cancel', Gds\LinkType::class, array_merge(
                        $options['cancel_link_options'],
                        ['label' => new TranslatableMessage('actions.cancel', [], 'messages')]
                    ));
            });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'error_mapping' => [
                'vehicle' => 'fuel',
            ],
        ]);

        $resolver->setRequired('cancel_link_options');
        $resolver->setAllowedTypes('cancel_link_options', ['array']);
        // Bundle's AbstractConfirmAction passes confirm_button_options too —
        // accept (and ignore) it; this form builds its own button.
        $resolver->setDefault('confirm_button_options', []);
        $resolver->setAllowedTypes('confirm_button_options', ['array']);

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
