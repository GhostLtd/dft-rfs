<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\TripController;
use App\Workflow\InternationalSurvey\TripState as StateObject;

return static function (ContainerConfigurator $container) {
    $commonEditConfig = [
        'to' =>  StateObject::STATE_SUMMARY,
        'name' => 'finish_editing',
        'guard' => '!is_empty(subject.getSubject().getId())',
        'metadata' => [
            'persist' => true,
            'redirectRoute' => [
                'routeName' => TripController::TRIP_ROUTE,
                'parameterMappings' => ['id' => 'id'],
            ],
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'international_survey_trip' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_ORIGIN_AND_DESTINATION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_ORIGIN_AND_DESTINATION,
                    StateObject::STATE_DATES,
                    StateObject::STATE_OUTBOUND_PORTS,
                    StateObject::STATE_RETURN_PORTS,
                    StateObject::STATE_DISTANCE,
                    StateObject::STATE_COUNTRIES_TRANSITTED,

                    StateObject::STATE_SUMMARY,
                ],
                'transitions' => [
                    'places entered' => [
                        'from' =>  StateObject::STATE_ORIGIN_AND_DESTINATION,
                        'to' => StateObject::STATE_DATES,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'dates entered' => [
                        'from' => StateObject::STATE_DATES,
                        'to' =>  StateObject::STATE_OUTBOUND_PORTS,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'outbound ports entered' => [
                        'from' => StateObject::STATE_OUTBOUND_PORTS,
                        'to' =>  StateObject::STATE_RETURN_PORTS,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'return ports entered' => [
                        'from' => StateObject::STATE_RETURN_PORTS,
                        'to' =>  StateObject::STATE_DISTANCE,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'distance entered' => [
                        'from' => StateObject::STATE_DISTANCE,
                        'to' =>  StateObject::STATE_COUNTRIES_TRANSITTED,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => ['id' => 'id'],
                            ],
                        ],
                        'from' => StateObject::STATE_COUNTRIES_TRANSITTED,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],


                    // Editing

                    'places changed' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_ORIGIN_AND_DESTINATION,
                    ]),
                    'dates changed' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_DATES,
                    ]),
                    'outbound ports changed' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_OUTBOUND_PORTS,
                    ]),
                    'return ports changed' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_RETURN_PORTS,
                    ]),
                    'distance changed' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_DISTANCE,
                    ]),
                ]
            ],
        ],
    ]);
};