<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\TripController;
use App\Workflow\InternationalSurvey\TripState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_trip' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_REQUEST_TRIP_DATES,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_REQUEST_TRIP_DATES,
                    StateObject::STATE_REQUEST_TRIP_OUTBOUND_PORTS,
                    StateObject::STATE_REQUEST_TRIP_OUTBOUND_CARGO_STATE,
                    StateObject::STATE_REQUEST_TRIP_RETURN_PORTS,
                    StateObject::STATE_REQUEST_TRIP_RETURN_CARGO_STATE,
                    StateObject::STATE_REQUEST_TRIP_DISTANCE,
                    StateObject::STATE_REQUEST_TRIP_COUNTRIES_TRANSITTED,

                    StateObject::STATE_SUMMARY,

                    StateObject::STATE_CHANGE_TRIP_DATES,
                    StateObject::STATE_CHANGE_TRIP_OUTBOUND_PORTS,
                    StateObject::STATE_CHANGE_TRIP_RETURN_PORTS,
                    StateObject::STATE_CHANGE_TRIP_OUTBOUND_CARGO_STATE,
                    StateObject::STATE_CHANGE_TRIP_RETURN_CARGO_STATE,
                    StateObject::STATE_CHANGE_TRIP_DISTANCE,
                    StateObject::STATE_CHANGE_TRIP_COUNTRIES_TRANSITTED,
                ],
                'transitions' => [
                    'dates entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_DATES,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_OUTBOUND_PORTS,
                    ],
                    'outbound ports entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_OUTBOUND_PORTS,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_OUTBOUND_CARGO_STATE,
                    ],
                    'outbound cargo entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_OUTBOUND_CARGO_STATE,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_RETURN_PORTS,
                    ],
                    'return ports entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_RETURN_PORTS,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_RETURN_CARGO_STATE,
                    ],
                    'return cargo entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_RETURN_CARGO_STATE,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_DISTANCE,
                    ],
                    'distance entered' => [
                        'from' => StateObject::STATE_REQUEST_TRIP_DISTANCE,
                        'to' =>  StateObject::STATE_REQUEST_TRIP_COUNTRIES_TRANSITTED,
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => ['id' => 'id'],
                            ],
                        ],
                        'from' => StateObject::STATE_REQUEST_TRIP_COUNTRIES_TRANSITTED,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'dates change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_TRIP_DATES,
                    ],
                    'dates changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => ['id' => 'id'],
                            ],
                        ],
                        'from' =>  StateObject::STATE_CHANGE_TRIP_DATES,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'outbound change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_TRIP_OUTBOUND_PORTS,
                    ],
                    'outbound ports changed' => [
                        'from' =>  StateObject::STATE_CHANGE_TRIP_OUTBOUND_PORTS,
                        'to' =>  StateObject::STATE_CHANGE_TRIP_OUTBOUND_CARGO_STATE,
                    ],
                    'outbound cargo state changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => ['id' => 'id'],
                            ],
                        ],
                        'from' =>  StateObject::STATE_CHANGE_TRIP_OUTBOUND_CARGO_STATE,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'return change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_TRIP_RETURN_PORTS,
                    ],
                    'return ports changed' => [
                        'from' =>  StateObject::STATE_CHANGE_TRIP_RETURN_PORTS,
                        'to' =>  StateObject::STATE_CHANGE_TRIP_RETURN_CARGO_STATE,
                    ],
                    'return cargo state changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => ['id' => 'id'],
                            ],
                        ],
                        'from' =>  StateObject::STATE_CHANGE_TRIP_RETURN_CARGO_STATE,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'distance change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_TRIP_DISTANCE,
                    ],
                    'distance changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => ['id' => 'id'],
                            ],
                        ],
                        'from' =>  StateObject::STATE_CHANGE_TRIP_DISTANCE,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'countries transitted change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_TRIP_COUNTRIES_TRANSITTED,
                    ],
                    'countries transitted changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => ['id' => 'id'],
                            ],
                        ],
                        'from' =>  StateObject::STATE_CHANGE_TRIP_COUNTRIES_TRANSITTED,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                ]
            ],
        ],
    ]);
};