<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\DomesticSurveyResponse;
use App\Workflow\DomesticSurveyInitialDetailsState;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_initial_details' => [
                'type' => 'state_machine',
                'initial_marking' => DomesticSurveyInitialDetailsState::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [DomesticSurveyInitialDetailsState::class],
                'places' => [
                    DomesticSurveyInitialDetailsState::STATE_INTRODUCTION,
                    DomesticSurveyInitialDetailsState::STATE_REQUEST_CONTACT_DETAILS,
                    DomesticSurveyInitialDetailsState::STATE_CHANGE_CONTACT_DETAILS,
                    DomesticSurveyInitialDetailsState::STATE_ASK_COMPLETABLE,
                    DomesticSurveyInitialDetailsState::STATE_ASK_ON_HIRE,
                    DomesticSurveyInitialDetailsState::STATE_SUMMARY,
                    DomesticSurveyInitialDetailsState::STATE_ASK_REASON_CANT_COMPLETE,
                    DomesticSurveyInitialDetailsState::STATE_ASK_HIREE_DETAILS,
                    DomesticSurveyInitialDetailsState::STATE_ASK_SCRAPPED_DETAILS,
                    DomesticSurveyInitialDetailsState::STATE_ASK_SOLD_DETAILS,
                ],
                'transitions' => [
                    'start' => [
                        'from' => DomesticSurveyInitialDetailsState::STATE_INTRODUCTION,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_REQUEST_CONTACT_DETAILS,
                    ],
                    'contact details entered' => [
                        'from' => DomesticSurveyInitialDetailsState::STATE_REQUEST_CONTACT_DETAILS,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_ASK_COMPLETABLE,
                    ],
                    'survey can be completed' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'ableToComplete', 'value' => true]],
                        'from' => DomesticSurveyInitialDetailsState::STATE_ASK_COMPLETABLE,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_ASK_ON_HIRE,
                    ],
                    'survey cannot be completed' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'ableToComplete', 'value' => false]],
                        'from' => DomesticSurveyInitialDetailsState::STATE_ASK_COMPLETABLE,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_ASK_REASON_CANT_COMPLETE
                    ],
                    'request hiree details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_ON_HIRE]],
                        'from' => [DomesticSurveyInitialDetailsState::STATE_ASK_ON_HIRE, DomesticSurveyInitialDetailsState::STATE_ASK_REASON_CANT_COMPLETE],
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_ASK_HIREE_DETAILS,
                    ],
                    'request scrapped details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_SCRAPPED_OR_STOLEN]],
                        'from' => DomesticSurveyInitialDetailsState::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_ASK_SCRAPPED_DETAILS,
                    ],
                    'request sold details' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => DomesticSurveyResponse::REASON_SOLD]],
                        'from' => DomesticSurveyInitialDetailsState::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_ASK_SOLD_DETAILS,
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                        ],
                        'from' => [
                            DomesticSurveyInitialDetailsState::STATE_ASK_ON_HIRE,
                            DomesticSurveyInitialDetailsState::STATE_ASK_HIREE_DETAILS,
                            DomesticSurveyInitialDetailsState::STATE_ASK_SCRAPPED_DETAILS,
                            DomesticSurveyInitialDetailsState::STATE_ASK_SOLD_DETAILS,
                        ],
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_SUMMARY,
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
                        'from' => DomesticSurveyInitialDetailsState::STATE_ASK_REASON_CANT_COMPLETE,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_SUMMARY,
                    ],
                    'change contact details' => [
                        'from' =>  DomesticSurveyInitialDetailsState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_CHANGE_CONTACT_DETAILS,
                    ],
                    'contact details changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index'
                        ],
                        'from' =>  DomesticSurveyInitialDetailsState::STATE_CHANGE_CONTACT_DETAILS,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_SUMMARY,
                    ],
                    'change can complete' => [
                        'from' =>  DomesticSurveyInitialDetailsState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_ASK_COMPLETABLE,
                    ],
                    'change hiree details' => [
                        'from' =>  DomesticSurveyInitialDetailsState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyInitialDetailsState::STATE_ASK_HIREE_DETAILS,
                    ],
                ]
            ],
        ],
    ]);
};