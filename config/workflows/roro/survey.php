<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\RoRo\Survey;
use App\Entity\SurveyStateInterface as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'roro_survey' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_NEW,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [Survey::class],
                'places' => [
                    StateObject::STATE_NEW,
                    StateObject::STATE_IN_PROGRESS,
                    StateObject::STATE_CLOSED,
                    StateObject::STATE_APPROVED,
                ],
                'transitions' => [
                    'started' => [
                        'from' => StateObject::STATE_NEW,
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
                ]
            ],
        ],
    ]);
};