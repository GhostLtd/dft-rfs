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
                    StateObject::STATE_SWAPPED_TRAILER,
                    StateObject::STATE_CHANGED_BODY_TYPE,
                    StateObject::STATE_NEW_VEHICLE_WEIGHTS,
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
                        'to' =>  StateObject::STATE_SWAPPED_TRAILER,
                        'guard' => "is_empty(subject.getSubject().getId())",
                    ],
                    'didnt swap trailer entered cant change body type' => [
                        'from' =>  StateObject::STATE_SWAPPED_TRAILER,
                        'to' =>  StateObject::STATE_DISTANCE,
                        'guard' => 'is_empty(subject.getSubject().getId())
                                && !subject.getSubject().canChangeBodyType()
                                && !subject.getSubject().getIsSwappedTrailer()',
                    ],
                    'swapped trailer entered cant change body type' => [
                        'from' =>  StateObject::STATE_SWAPPED_TRAILER,
                        'to' =>  StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'guard' => '!subject.getSubject().canChangeBodyType()
                                && subject.getSubject().getIsSwappedTrailer()',
                    ],
                    'swapped trailer entered can change body type' => [
                        'from' => StateObject::STATE_SWAPPED_TRAILER,
                        'to' =>  StateObject::STATE_CHANGED_BODY_TYPE,
                        'guard' => "subject.getSubject().canChangeBodyType()",
                    ],
                    'can change body type but not changed trailer or body' => [
                        'from' => StateObject::STATE_CHANGED_BODY_TYPE,
                        'to' =>  StateObject::STATE_DISTANCE,
                        'guard' => "is_empty(subject.getSubject().getId())
                                && !subject.getSubject().canChangeWeights()",
                    ],

                    'can change body and changed trailer or body' => [
                        'from' => StateObject::STATE_CHANGED_BODY_TYPE,
                        'to' =>  StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'guard' => "subject.getSubject().canChangeWeights()",
                    ],

                    'new vehicle weights entered can change body type' => [
                        'from' => StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'to' =>  StateObject::STATE_DISTANCE,
                        'guard' => "is_empty(subject.getSubject().getId())",
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

                    'editing places' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_ORIGIN_AND_DESTINATION,
                    ]),
                    'editing dates' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_DATES,
                    ]),
                    'editing outbound ports' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_OUTBOUND_PORTS,
                    ]),
                    'editing return ports' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_RETURN_PORTS,
                    ]),
                    'editing trailer cant change body type' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_SWAPPED_TRAILER,
                        'guard' => '!is_empty(subject.getSubject().getId())
                                && !subject.getSubject().canChangeBodyType()
                                && !subject.getSubject().getIsSwappedTrailer()',
                        'metadata' => array_merge($commonEditConfig['metadata'], [
                            'submitLabel' => 'common.actions.continue',
                        ]),
                    ]),

                    'editing body type not changed' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_CHANGED_BODY_TYPE,
                        'guard' => "!is_empty(subject.getSubject().getId())
                                && !subject.getSubject().canChangeWeights()",
                    ]),
                    'editing vehicle weights changed' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'guard' => "!is_empty(subject.getSubject().getId())
                                && subject.getSubject().canChangeWeights()",
                    ]),


                    'editing distance' => array_merge($commonEditConfig, [
                        'from' =>  StateObject::STATE_DISTANCE,
                    ]),
                ]
            ],
        ],
    ]);
};