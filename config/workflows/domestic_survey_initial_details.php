<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\DomesticSurveyResponse;
use App\Workflow\DomesticSurveyState;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_initial_details' => [
                'type' => 'state_machine',
                'initial_marking' => DomesticSurveyState::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [DomesticSurveyState::class],
                'places' => [
                    DomesticSurveyState::STATE_INTRODUCTION,
                    DomesticSurveyState::STATE_REQUEST_CONTACT_DETAILS,
                    DomesticSurveyState::STATE_CHANGE_CONTACT_DETAILS,
                    DomesticSurveyState::STATE_ASK_COMPLETABLE,
                    DomesticSurveyState::STATE_ASK_ON_HIRE,
                    DomesticSurveyState::STATE_SUMMARY,
                    DomesticSurveyState::STATE_ASK_REASON_CANT_COMPLETE,
                    DomesticSurveyState::STATE_ASK_HIREE_DETAILS,
                    DomesticSurveyState::STATE_ASK_SCRAPPED_DETAILS,
                    DomesticSurveyState::STATE_ASK_SOLD_DETAILS,
                ],
                'transitions' => [
                    'start' => [
                        'from' => DomesticSurveyState::STATE_INTRODUCTION,
                        'to' =>  DomesticSurveyState::STATE_REQUEST_CONTACT_DETAILS,
                    ],
                    'contact details entered' => [
                        'from' => DomesticSurveyState::STATE_REQUEST_CONTACT_DETAILS,
                        'to' =>  DomesticSurveyState::STATE_ASK_COMPLETABLE,
                    ],
                    'survey can be completed' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'ableToComplete', 'value' => true]],
                        'from' => DomesticSurveyState::STATE_ASK_COMPLETABLE,
                        'to' =>  DomesticSurveyState::STATE_ASK_ON_HIRE,
                    ],
                    'survey cannot be completed' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'ableToComplete', 'value' => false]],
                        'from' => DomesticSurveyState::STATE_ASK_COMPLETABLE,
                        'to' =>  DomesticSurveyState::STATE_ASK_REASON_CANT_COMPLETE
                    ],
                    'request hiree details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_ON_HIRE]],
                        'from' => [DomesticSurveyState::STATE_ASK_ON_HIRE, DomesticSurveyState::STATE_ASK_REASON_CANT_COMPLETE],
                        'to' =>  DomesticSurveyState::STATE_ASK_HIREE_DETAILS,
                    ],
                    'request scrapped details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_SCRAPPED_OR_STOLEN]],
                        'from' => DomesticSurveyState::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  DomesticSurveyState::STATE_ASK_SCRAPPED_DETAILS,
                    ],
                    'request sold details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_SOLD]],
                        'from' => DomesticSurveyState::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  DomesticSurveyState::STATE_ASK_SOLD_DETAILS,
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                        ],
                        'from' => [
                            DomesticSurveyState::STATE_ASK_ON_HIRE,
                            DomesticSurveyState::STATE_ASK_HIREE_DETAILS,
                            DomesticSurveyState::STATE_ASK_SCRAPPED_DETAILS,
                            DomesticSurveyState::STATE_ASK_SOLD_DETAILS,
                        ],
                        'to' =>  DomesticSurveyState::STATE_SUMMARY,
                    ],
                    'finish_2' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                            'transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' =>
                                array_diff(array_values(DomesticSurveyResponse::UNABLE_TO_COMPLETE_REASONS), [
                                    DomesticSurveyResponse::REASON_ON_HIRE,
                                    DomesticSurveyResponse::REASON_SOLD,
                                    DomesticSurveyResponse::REASON_SCRAPPED_OR_STOLEN,
                                ]),
                            ]
                        ],
                        'from' => DomesticSurveyState::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  DomesticSurveyState::STATE_SUMMARY,
                    ],
                    'change contact details' => [
                        'from' =>  DomesticSurveyState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyState::STATE_CHANGE_CONTACT_DETAILS,
                    ],
                    'contact details changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index'
                        ],
                        'from' =>  DomesticSurveyState::STATE_CHANGE_CONTACT_DETAILS,
                        'to' =>  DomesticSurveyState::STATE_SUMMARY,
                    ],
                    'change can complete' => [
                        'from' =>  DomesticSurveyState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyState::STATE_ASK_COMPLETABLE,
                    ],
                    'change hiree details' => [
                        'from' =>  DomesticSurveyState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyState::STATE_ASK_HIREE_DETAILS,
                    ],
                ]
            ],
        ],
    ]);
};