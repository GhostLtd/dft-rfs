<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\InternationalSurvey\VehicleState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_vehicle' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_REQUEST_VEHICLE_REGISTRATION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_REQUEST_VEHICLE_REGISTRATION,
                    StateObject::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION,
                    StateObject::STATE_REQUEST_VEHICLE_BODY,
                    StateObject::STATE_REQUEST_VEHICLE_WEIGHT,
                    StateObject::STATE_REQUEST_TRAVEL_DATES,
                    StateObject::STATE_SUMMARY,
                ],
                'transitions' => [
                    'vehicle details entered' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_REGISTRATION,
                        'to' =>  StateObject::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION,
                    ],
                    'axle configuration entered' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION,
                        'to' =>  StateObject::STATE_REQUEST_VEHICLE_BODY,
                    ],
                    'vehicle body entered' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_BODY,
                        'to' =>  StateObject::STATE_REQUEST_VEHICLE_WEIGHT,
                    ],
                    'vehicle weight entered' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_WEIGHT,
                        'to' =>  StateObject::STATE_REQUEST_TRAVEL_DATES,
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'TODO-ROUTE',
                        ],
                        'from' => StateObject::STATE_REQUEST_TRAVEL_DATES,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                ]
            ],
        ],
    ]);
};