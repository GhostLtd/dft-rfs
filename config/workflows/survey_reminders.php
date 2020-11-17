<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\Domestic\Survey;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'survey_reminders' => [
                'type' => 'state_machine',
                'initial_marking' => Survey::REMINDER_STATE_INITIAL,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'reminderState',
                ],
                'supports' => Survey::class,
                'places' => [
                    Survey::REMINDER_STATE_INITIAL,
                    Survey::REMINDER_STATE_NOT_WANTED,
                    Survey::REMINDER_STATE_WANTED,
                    Survey::REMINDER_STATE_SENT,
                ],
                'transitions' => [
                    'do-not-want' => [
                        'from' => Survey::REMINDER_STATE_INITIAL,
                        'to' =>  Survey::REMINDER_STATE_NOT_WANTED,
                    ],
                    'wanted' => [
                        'from' => Survey::REMINDER_STATE_INITIAL,
                        'to' =>  Survey::REMINDER_STATE_WANTED,
                    ],
                    'sent' => [
                        'from' => Survey::REMINDER_STATE_WANTED,
                        'to' =>  Survey::REMINDER_STATE_SENT,
                    ],
                ]
            ],
        ],
    ]);
};