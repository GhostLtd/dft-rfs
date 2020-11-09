<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\DomesticSurvey;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'survey_reminders' => [
                'type' => 'state_machine',
                'initial_marking' => DomesticSurvey::REMINDER_STATE_INITIAL,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'reminderState',
                ],
                'supports' => DomesticSurvey::class,
                'places' => [
                    DomesticSurvey::REMINDER_STATE_INITIAL,
                    DomesticSurvey::REMINDER_STATE_NOT_WANTED,
                    DomesticSurvey::REMINDER_STATE_WANTED,
                    DomesticSurvey::REMINDER_STATE_SENT,
                ],
                'transitions' => [
                    'do-not-want' => [
                        'from' => DomesticSurvey::REMINDER_STATE_INITIAL,
                        'to' =>  DomesticSurvey::REMINDER_STATE_NOT_WANTED,
                    ],
                    'wanted' => [
                        'from' => DomesticSurvey::REMINDER_STATE_INITIAL,
                        'to' =>  DomesticSurvey::REMINDER_STATE_WANTED,
                    ],
                    'sent' => [
                        'from' => DomesticSurvey::REMINDER_STATE_WANTED,
                        'to' =>  DomesticSurvey::REMINDER_STATE_SENT,
                    ],
                ]
            ],
        ],
    ]);
};