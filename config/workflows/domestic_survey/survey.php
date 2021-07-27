<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\Domestic\Survey as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_NEW,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_NEW,
                    StateObject::STATE_INVITATION_PENDING,
                    StateObject::STATE_INVITATION_SENT,
                    StateObject::STATE_INVITATION_FAILED,
                    StateObject::STATE_IN_PROGRESS,
                    StateObject::STATE_CLOSED,
                    StateObject::STATE_APPROVED,
                    StateObject::STATE_REJECTED,
                    StateObject::STATE_EXPORTING,
                    StateObject::STATE_EXPORTED,
                ],
                'transitions' => [
                    'invite_user' => [
                        'from' => StateObject::STATE_NEW,
                        'to' => StateObject::STATE_INVITATION_PENDING,
                        // this transition has a guard event subscriber
                    ],
                    'invitation_sent' => [
                        'from' => StateObject::STATE_INVITATION_PENDING,
                        'to' => StateObject::STATE_INVITATION_SENT,
                    ],
                    'invitation_failed' => [
                        'from' => StateObject::STATE_INVITATION_PENDING,
                        'to' => StateObject::STATE_INVITATION_FAILED,
                    ],
                    'started' => [
                        'from' => [StateObject::STATE_INVITATION_SENT, StateObject::STATE_INVITATION_PENDING, StateObject::STATE_NEW],
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
                    'approve' => [
                        'from' => StateObject::STATE_CLOSED,
                        'to' => StateObject::STATE_APPROVED,
                    ],
                    'un_approve' => [
                        'from' => StateObject::STATE_APPROVED,
                        'to' => StateObject::STATE_CLOSED,
                    ],
                    'start_export' => [
                        'from' => StateObject::STATE_APPROVED,
                        'to' => StateObject::STATE_EXPORTING,
                    ],
                    'cancel_export' => [
                        'from' => StateObject::STATE_EXPORTING,
                        'to' => StateObject::STATE_APPROVED,
                    ],
                    'confirm_export' => [
                        'from' => StateObject::STATE_EXPORTING,
                        'to' => StateObject::STATE_EXPORTED,
                    ],
                    'reject' => [
                        'from' => [StateObject::STATE_CLOSED, StateObject::STATE_INVITATION_FAILED],
                        'to' => StateObject::STATE_REJECTED,
                    ],
                    'un_reject' => [
                        'from' => StateObject::STATE_REJECTED,
                        'to' => StateObject::STATE_CLOSED,
                        // this transition has a guard event subscriber
                    ],
                ]
            ],
        ],
    ]);
};