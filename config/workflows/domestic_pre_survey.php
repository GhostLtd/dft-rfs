<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\DomesticSurvey;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_pre_survey' => [
                'type' => 'state_machine',
                'initial_marking' => DomesticSurvey::STATE_PRE_SURVEY_INTRODUCTION,
                'marking_store' => [
//                    'type' => 'single_state',
//                    'arguments' => ['$property' => 'state'],
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [DomesticSurvey::class],
                'places' => [
                    DomesticSurvey::STATE_PRE_SURVEY_INTRODUCTION,
                    DomesticSurvey::STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS,
                    DomesticSurvey::STATE_PRE_SURVEY_ASK_COMPLETABLE,
                    DomesticSurvey::STATE_PRE_SURVEY_ASK_ON_HIRE,
//                    DomesticSurvey::STATE_PRE_SURVEY_ASK_REMINDER_EMAIL,
                    DomesticSurvey::STATE_PRE_SURVEY_SUMMARY,
                    DomesticSurvey::STATE_PRE_SURVEY_ASK_REASON_CANT_COMPLETE,
                    DomesticSurvey::STATE_PRE_SURVEY_ASK_HIREE_DETAILS,
                ],
                'transitions' => [
                    'start' => [
                        'from' => DomesticSurvey::STATE_PRE_SURVEY_INTRODUCTION,
                        'to' =>  DomesticSurvey::STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS,
                    ],
                    'contact details entered' => [
                        'from' => DomesticSurvey::STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS,
                        'to' =>  DomesticSurvey::STATE_PRE_SURVEY_ASK_COMPLETABLE,
                    ],
                    'survey can be completed' => [
                        'metadata' => ['result' => 'yes'],
                        'from' => DomesticSurvey::STATE_PRE_SURVEY_ASK_COMPLETABLE,
                        'to' =>  DomesticSurvey::STATE_PRE_SURVEY_ASK_ON_HIRE,
                    ],
                    'survey cannot be completed' => [
                        'metadata' => ['result' => 'no'],
                        'from' => DomesticSurvey::STATE_PRE_SURVEY_ASK_COMPLETABLE,
                        'to' =>  DomesticSurvey::STATE_PRE_SURVEY_ASK_REASON_CANT_COMPLETE
                    ],
                    'request hiree details' => [
                        'metadata' => ['result' => 'on-hire'],
                        'from' => [DomesticSurvey::STATE_PRE_SURVEY_ASK_ON_HIRE, DomesticSurvey::STATE_PRE_SURVEY_ASK_REASON_CANT_COMPLETE],
                        'to' =>  DomesticSurvey::STATE_PRE_SURVEY_ASK_HIREE_DETAILS,
                    ],
//                    'vehicle not on hire' => [
//                        'from' => DomesticSurvey::STATE_PRE_SURVEY_ASK_ON_HIRE,
//                        'to' =>  DomesticSurvey::STATE_PRE_SURVEY_ASK_REMINDER_EMAIL,
//                    ],
//                    'finish' => [
//                        'from' => [DomesticSurvey::STATE_PRE_SURVEY_ASK_REMINDER_EMAIL, DomesticSurvey::STATE_PRE_SURVEY_ASK_HIREE_DETAILS],
//                        'to' =>  DomesticSurvey::STATE_PRE_SURVEY_SUMMARY,
//                    ],
                    'finish' => [
                        'from' => [DomesticSurvey::STATE_PRE_SURVEY_ASK_ON_HIRE, DomesticSurvey::STATE_PRE_SURVEY_ASK_HIREE_DETAILS],
                        'to' =>  DomesticSurvey::STATE_PRE_SURVEY_SUMMARY,
                    ],
                ]
            ],
        ],
    ]);
};