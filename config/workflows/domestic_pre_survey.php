<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\DomesticSurveyState;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_pre_survey' => [
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
                        'metadata' => ['transitionWhenFormData' => ['property' => 'unableToCompleteReason', 'value' => 'on-hire']],
                        'from' => [DomesticSurveyState::STATE_ASK_ON_HIRE, DomesticSurveyState::STATE_ASK_REASON_CANT_COMPLETE],
                        'to' =>  DomesticSurveyState::STATE_ASK_HIREE_DETAILS,
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index'
                        ],
                        'from' => [DomesticSurveyState::STATE_ASK_ON_HIRE, DomesticSurveyState::STATE_ASK_HIREE_DETAILS],
                        'to' =>  DomesticSurveyState::STATE_SUMMARY,
                    ],
                    'change_contact_details' => [
                        'from' =>  DomesticSurveyState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyState::STATE_CHANGE_CONTACT_DETAILS,
                    ],
                    'contact_details_changed' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index'
                        ],
                        'from' =>  DomesticSurveyState::STATE_CHANGE_CONTACT_DETAILS,
                        'to' =>  DomesticSurveyState::STATE_SUMMARY,
                    ],
                    'change_can_complete' => [
                        'from' =>  DomesticSurveyState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyState::STATE_ASK_COMPLETABLE,
                    ],
                    'change_hiree_details' => [
                        'from' =>  DomesticSurveyState::STATE_SUMMARY,
                        'to' =>  DomesticSurveyState::STATE_ASK_HIREE_DETAILS,
                    ],
                ]
            ],
        ],
    ]);
};