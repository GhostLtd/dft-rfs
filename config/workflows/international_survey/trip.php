<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\TripController;
use App\Workflow\InternationalSurvey\TripState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_trip' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_REQUEST_TRIP_OUTBOUND_PORTS,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_REQUEST_TRIP_OUTBOUND_PORTS,
                    StateObject::STATE_REQUEST_TRIP_OUTBOUND_CARGO,
                    StateObject::STATE_REQUEST_TRIP_RETURN_PORTS,
                    StateObject::STATE_REQUEST_TRIP_RETURN_CARGO,
                    StateObject::STATE_REQUEST_TRIP_DISTANCE,
                    StateObject::STATE_REQUEST_TRIP_TRANSITTED_COUNTRIES,

                    StateObject::STATE_SUMMARY,
                ],
                'transitions' => [
                    'outbound ports entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_OUTBOUND_PORTS,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_OUTBOUND_CARGO,
                    ],
                    'outbound cargo entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_OUTBOUND_CARGO,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_RETURN_PORTS,
                    ],
                    'return ports entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_RETURN_PORTS,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_RETURN_CARGO,
                    ],
                    'return cargo entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_RETURN_CARGO,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_DISTANCE,
                    ],
                    'distance entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_DISTANCE,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_TRANSITTED_COUNTRIES,
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => ['id' => 'id'],
                            ],
                        ],
                        'from' => StateObject::STATE_REQUEST_TRIP_TRANSITTED_COUNTRIES,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                ]
            ],
        ],
    ]);
};