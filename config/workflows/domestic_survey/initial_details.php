<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\DomesticSurveyResponse;
use App\Workflow\DomesticSurvey\InitialDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_initial_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_INTRODUCTION,
                    StateObject::STATE_REQUEST_CONTACT_DETAILS,
                    StateObject::STATE_CHANGE_CONTACT_DETAILS,
                    StateObject::STATE_ASK_COMPLETABLE,
                    StateObject::STATE_ASK_ON_HIRE,
                    StateObject::STATE_SUMMARY,
                    StateObject::STATE_ASK_REASON_CANT_COMPLETE,
                    StateObject::STATE_ASK_HIREE_DETAILS,
                    StateObject::STATE_ASK_SCRAPPED_DETAILS,
                    StateObject::STATE_ASK_SOLD_DETAILS,
                ],
                'transitions' => [
                    'start' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_REQUEST_CONTACT_DETAILS,
                    ],
                    'contact details entered' => [
                        'from' => StateObject::STATE_REQUEST_CONTACT_DETAILS,
                        'to' =>  StateObject::STATE_ASK_COMPLETABLE,
                    ],
                    'survey can be completed' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'ableToComplete', 'value' => true]],
                        'from' => StateObject::STATE_ASK_COMPLETABLE,
                        'to' =>  StateObject::STATE_ASK_ON_HIRE,
                    ],
                    'survey cannot be completed' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'ableToComplete', 'value' => false]],
                        'from' => StateObject::STATE_ASK_COMPLETABLE,
                        'to' =>  StateObject::STATE_ASK_REASON_CANT_COMPLETE
                    ],
                    'request hiree details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_ON_HIRE]],
                        'from' => [StateObject::STATE_ASK_ON_HIRE, StateObject::STATE_ASK_REASON_CANT_COMPLETE],
                        'to' =>  StateObject::STATE_ASK_HIREE_DETAILS,
                    ],
                    'request scrapped details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_SCRAPPED_OR_STOLEN]],
                        'from' => StateObject::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  StateObject::STATE_ASK_SCRAPPED_DETAILS,
                    ],
                    'request sold details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_SOLD]],
                        'from' => StateObject::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  StateObject::STATE_ASK_SOLD_DETAILS,
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                        ],
                        'from' => [
                            StateObject::STATE_ASK_ON_HIRE,
                            StateObject::STATE_ASK_HIREE_DETAILS,
                            StateObject::STATE_ASK_SCRAPPED_DETAILS,
                            StateObject::STATE_ASK_SOLD_DETAILS,
                        ],
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'finish_2' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                            'transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' =>
                                array_diff(array_values(DomesticSurveyResponse::UNABLE_TO_COMPLETE_REASON_CHOICES), [
                                    DomesticSurveyResponse::REASON_ON_HIRE,
                                    DomesticSurveyResponse::REASON_SOLD,
                                    DomesticSurveyResponse::REASON_SCRAPPED_OR_STOLEN,
                                ]),
                            ]
                        ],
                        'from' => StateObject::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'change contact details' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_CONTACT_DETAILS,
                    ],
                    'contact details changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index'
                        ],
                        'from' =>  StateObject::STATE_CHANGE_CONTACT_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'change can complete' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_ASK_COMPLETABLE,
                    ],
                    'change hiree details' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_ASK_HIREE_DETAILS,
                    ],
                ]
            ],
        ],
    ]);
};