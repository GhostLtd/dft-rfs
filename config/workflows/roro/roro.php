<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\RoRo\RoRoState as StateObject;

return static function (ContainerConfigurator $container) {
    $endMetadata = [
        'persist' => true,
        'redirectRoute' => ['routeName' => 'app_roro_survey_view', 'parameterMappings' => ['surveyId' => 'id']],
    ];

    $container->extension('framework', [
        'workflows' => [
            'roro' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_INTRODUCTION,
                    StateObject::STATE_VEHICLE_COUNTS,
                    StateObject::STATE_FINISH,
                ],
                'transitions' => [
                    'not-active' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => array_merge($endMetadata, [
                            'submitLabel' => 'common.actions.continue'
                        ]),
                        'guard' => '!subject.getSubject().getIsActiveForPeriod()',
                    ],
                    'active' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_VEHICLE_COUNTS,
                        'guard' => 'subject.getSubject().getIsActiveForPeriod()',
                    ],
                    'finish' => [
                        'from' => StateObject::STATE_VEHICLE_COUNTS,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => $endMetadata,
                    ],
                ]
            ],
        ],
    ]);
};