<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\IndexController;
use App\Form\InternationalSurvey\ClosingDetails\EarlyResponseType;
use App\Form\InternationalSurvey\ClosingDetails\LoadingWithoutUnloadingType;
use App\Form\InternationalSurvey\ClosingDetails\ReasonEmptySurveyType;
use App\Workflow\InternationalSurvey\ClosingDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_closing_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_START,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_START,
                    ],
                    [
                        'name' => StateObject::STATE_EARLY_RESPONSE,
                        'metadata' => [
                            'template' => 'international_survey/closing_details/form-early-response.html.twig',
                            'form_class' => EarlyResponseType::class,
                            'form_data_property' => null,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'metadata' => [
                            'template' => 'international_survey/closing_details/form-reason-for-empty-survey.html.twig',
                            'form_class' => ReasonEmptySurveyType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                        'metadata' => [
                            'template' => 'international_survey/closing_details/form-loading-without-unloading.html.twig',
                            'form_class' => LoadingWithoutUnloadingType::class,
                            'form_data_property' => null,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CONFIRM,
                        'metadata' => [
                            'template' => 'international_survey/closing_details/confirm.html.twig',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_END,
                    ],
                ],
                'transitions' => [
                    // Early response
                    'early_response' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_EARLY_RESPONSE,
                        'guard' => 'subject.getSubject().isEarlierThanSurveyPeriodEnd()',
                    ],
                    'early_response_to_not_filled_out' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'guard' => 'subject.getIsCorrect() === true && subject.getSubject().shouldAskWhyEmptySurvey()',
                    ],
                    'early_response_to_loading_without_unloading' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                        'guard' => 'subject.getIsCorrect() === true && !subject.getSubject().shouldAskWhyEmptySurvey() && irhs_has_loading_without_unloading(subject.getSubject())',
                    ],
                    'early_response_to_filled_out' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_CONFIRM,
                        'guard' => 'subject.getIsCorrect() === true && !subject.getSubject().shouldAskWhyEmptySurvey() && !irhs_has_loading_without_unloading(subject.getSubject())',
                    ],
                    'early_response_to_dashboard' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_END,
                        'guard' => 'subject.getIsCorrect() === false',
                        'metadata' => [
                            'redirect_route' => IndexController::SUMMARY_ROUTE,
                        ],
                    ],

                    // Not filled out
                    'not_filled_out' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'guard' => '!subject.getSubject().isEarlierThanSurveyPeriodEnd() && subject.getSubject().shouldAskWhyEmptySurvey()',
                    ],
                    'request_confirmation' => [
                        'from' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'to' => StateObject::STATE_CONFIRM,
                    ],

                    // Loading without unloading
                    'start_to_loading_but_not_unloading' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                        'guard' => '!subject.getSubject().isEarlierThanSurveyPeriodEnd() && !subject.getSubject().shouldAskWhyEmptySurvey() && 
  irhs_has_loading_without_unloading(subject.getSubject())',
                    ],
                    'loading_but_not_unloading' => [
                        'from' => StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                        'to' => StateObject::STATE_CONFIRM,
                        'guard' => 'subject.getIsCorrect() === true',
                    ],
                    'loading_but_not_unloading_to_dashboard' => [
                        'from' => StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                        'to' => StateObject::STATE_END,
                        'guard' => 'subject.getIsCorrect() === false',
                        'metadata' => [
                            'redirect_route' => IndexController::SUMMARY_ROUTE,
                        ],
                    ],

                    // Filled out
                    'filled_out' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_CONFIRM,
                        'guard' => '!subject.getSubject().isEarlierThanSurveyPeriodEnd() && !subject.getSubject().shouldAskWhyEmptySurvey()',
                    ],

                    // Confirm
                    'finish' => [
                        'from' => StateObject::STATE_CONFIRM,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'persist' => true,
                            'redirect_route' => IndexController::SUMMARY_ROUTE,
                            'submit_label' => 'Confirm and submit survey',
                        ],
                    ],
                ],
            ],
        ],
    ]);
};
