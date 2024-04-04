<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\IndexController;
use App\Workflow\InternationalSurvey\ClosingDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_closing_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_START,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_START,
                    StateObject::STATE_EARLY_RESPONSE,
                    StateObject::STATE_REASON_EMPTY_SURVEY,
                    StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                    StateObject::STATE_CONFIRM,
                    StateObject::STATE_END,
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
                        'guard' => 'subject.getSubject().shouldAskWhyEmptySurvey()',
                        'metadata' => [
                            'transitionWhenFormData' => ['property' => 'is_correct', 'value' => true]
                        ],
                    ],
                    'early_response_to_loading_without_unloading' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                        'guard' => '!subject.getSubject().shouldAskWhyEmptySurvey() && irhs_has_loading_without_unloading(subject.getSubject())',
                        'metadata' => [
                            'transitionWhenFormData' => ['property' => 'is_correct', 'value' => true]
                        ],
                    ],
                    'early_response_to_filled_out' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_CONFIRM,
                        'guard' => '!subject.getSubject().shouldAskWhyEmptySurvey() && !irhs_has_loading_without_unloading(subject.getSubject())',
                        'metadata' => [
                            'transitionWhenFormData' => ['property' => 'is_correct', 'value' => true]
                        ],
                    ],
                    'early_response_to_dashboard' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'redirectRoute' => IndexController::SUMMARY_ROUTE,
                            'transitionWhenFormData' => ['property' => 'is_correct', 'value' => false]
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
                        'guard' => '!subject.getSubject().isEarlierThanSurveyPeriodEnd() && !subject.getSubject().shouldAskWhyEmptySurvey() && irhs_has_loading_without_unloading(subject.getSubject())'
                    ],

                    'loading_but_not_unloading' => [
                        'from' => StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                        'to' => StateObject::STATE_CONFIRM,
                        'metadata' => [
                            'transitionWhenFormData' => ['property' => 'is_correct', 'value' => true]
                        ],
                    ],

                    'loading_but_not_unloading_to_dashboard' => [
                        'from' => StateObject::STATE_LOADING_WITHOUT_UNLOADING,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'redirectRoute' => IndexController::SUMMARY_ROUTE,
                            'transitionWhenFormData' => ['property' => 'is_correct', 'value' => false]
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
                        'to' =>  StateObject::STATE_END,
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => IndexController::SUMMARY_ROUTE,
                            'submitLabel' => 'Confirm and submit survey',
                        ],
                    ],
                ]
            ],
        ],
    ]);
};
