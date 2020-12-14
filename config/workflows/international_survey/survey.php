<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\International\Survey as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_NEW,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_NEW,
                    StateObject::STATE_INVITED_USER,
                    StateObject::STATE_REMINDED_USER,
                    StateObject::STATE_IN_PROGRESS,
                    StateObject::STATE_CLOSED,
                    StateObject::STATE_REJECTED,
                    StateObject::STATE_EXPORTED,
                ],
                'transitions' => [
                    'invite_user' => [
                        'from' => StateObject::STATE_NEW,
                        'to' => StateObject::STATE_INVITED_USER,
                    ],
                    'remind_user' => [
                        'from' => StateObject::STATE_INVITED_USER,
                        'to' => StateObject::STATE_REMINDED_USER,
                    ],
                    'started' => [
                        'from' => [StateObject::STATE_INVITED_USER, StateObject::STATE_REMINDED_USER, StateObject::STATE_NEW],
                        'to' => StateObject::STATE_IN_PROGRESS,
                    ],
                    'complete' => [
                        'from' => StateObject::STATE_IN_PROGRESS,
                        'to' => StateObject::STATE_CLOSED,
                    ],
                    're_open' => [
                        'from' => StateObject::STATE_CLOSED,
                        'to' => StateObject::STATE_IN_PROGRESS,
                    ],
                    'export' => [
                        'from' => StateObject::STATE_CLOSED,
                        'to' => StateObject::STATE_EXPORTED,
                    ],
                    'reject' => [
                        'from' => StateObject::STATE_CLOSED,
                        'to' => StateObject::STATE_REJECTED,
                    ],
                    'un_reject' => [
                        'from' => StateObject::STATE_REJECTED,
                        'to' => StateObject::STATE_CLOSED,
                    ],
                ]
            ],
        ],
    ]);
};