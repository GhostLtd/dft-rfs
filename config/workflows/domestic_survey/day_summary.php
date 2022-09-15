<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\DomesticSurvey\DaySummaryState as StateObject;

return static function (ContainerConfigurator $container) {
    $editEndMetadata =  [
        'persist' => true,
        'redirectRoute' => [
            'routeName' => 'app_domesticsurvey_day_view',
            'parameterMappings' => ['dayNumber' => 'day.number'],
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_day_summary' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_ORIGIN,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_INTRO,
                    StateObject::STATE_ORIGIN,
                    StateObject::STATE_DESTINATION,
                    StateObject::STATE_BORDER_CROSSING,
                    StateObject::STATE_DISTANCE_TRAVELLED,
                    StateObject::STATE_FURTHEST_STOP,
                    StateObject::STATE_GOODS_DESCRIPTION,
                    StateObject::STATE_HAZARDOUS_GOODS,
                    StateObject::STATE_CARGO_TYPE,
                    StateObject::STATE_GOODS_WEIGHT,
                    StateObject::STATE_NUMBER_OF_STOPS,
                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'intro_to_origin' => [
                        'from' => StateObject::STATE_INTRO,
                        'to' => StateObject::STATE_ORIGIN,
                    ],
                    'origin_to_destination' => [
                        'from' => StateObject::STATE_ORIGIN,
                        'to' => StateObject::STATE_DESTINATION,
                    ],
                    'destination_to_furthest_stop' => [
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_FURTHEST_STOP,
                    ],
                    'furthest_stop_to_border_crossing' => [
                        'from' => StateObject::STATE_FURTHEST_STOP,
                        'to' =>  StateObject::STATE_BORDER_CROSSING,
                        'guard' => 'subject.getSubject().getDay().getResponse().getSurvey().getIsNorthernIreland()',
                    ],
                    'border_crossing_to_distance_travelled' => [
                        'from' =>  StateObject::STATE_BORDER_CROSSING,
                        'to' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'guard' => 'is_empty(subject.getSubject().getId())'
                    ],
                    'furthest_stop_to_distance_travelled' => [
                        'from' => StateObject::STATE_FURTHEST_STOP,
                        'to' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'guard' => 'is_empty(subject.getSubject().getId())
                            && !subject.getSubject().getDay().getResponse().getSurvey().getIsNorthernIreland()',
                    ],
                    'distance_travelled_to_goods_description' => [
                        'from' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'to' =>  StateObject::STATE_GOODS_DESCRIPTION,
                        'guard' => 'is_empty(subject.getSubject().getId())'
                    ],
                    'goods_description_to_hazardous_goods' => [
                        'from' =>  StateObject::STATE_GOODS_DESCRIPTION,
                        'to' => StateObject::STATE_HAZARDOUS_GOODS,
                    ],
                    'hazardous_goods_to_cargo_type' => [
                        'from' => StateObject::STATE_HAZARDOUS_GOODS,
                        'to' => StateObject::STATE_CARGO_TYPE,
                    ],
                    'cargo_type_to_goods_weight' => [
                        'from' => StateObject::STATE_CARGO_TYPE,
                        'to' => StateObject::STATE_GOODS_WEIGHT,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'goods_weight_to_number_of_stops' => [
                        'from' => StateObject::STATE_GOODS_WEIGHT,
                        'to' => StateObject::STATE_NUMBER_OF_STOPS,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],


                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => 'app_domesticsurvey_day_view',
                                'parameterMappings' => ['dayNumber' => 'day.number'],
                            ],
                        ],
                        'from' => StateObject::STATE_NUMBER_OF_STOPS,
                        'to' =>  StateObject::STATE_END,
                    ],

                    'edit_locations_gb' => [
                        'from' => StateObject::STATE_FURTHEST_STOP,
                        'to' =>  StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())
                            && !subject.getSubject().getDay().getResponse().getSurvey().getIsNorthernIreland()',
                        'metadata' => $editEndMetadata,
                    ],
                    'edit_locations_ni' => [
                        'from' =>  StateObject::STATE_BORDER_CROSSING,
                        'to' => StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())',
                        'metadata' => $editEndMetadata,
                    ],


                    'edit_distance_travelled' => [
                        'from' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'to' =>  StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())',
                        'metadata' => $editEndMetadata,
                    ],


                    'edit_goods' => [
                        'from' => StateObject::STATE_CARGO_TYPE,
                        'to' => StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())',
                        'metadata' => $editEndMetadata,
                    ],
                    'edit_goods_weight' => [
                        'from' => StateObject::STATE_GOODS_WEIGHT,
                        'to' => StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())',
                        'metadata' => $editEndMetadata,
                    ],

                ]
            ],
        ],
    ]);
};