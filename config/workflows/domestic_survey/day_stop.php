<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\DomesticSurvey\DayStopState as StateObject;

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
            'domestic_survey_day_stop' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_ORIGIN,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_ORIGIN,
                    StateObject::STATE_DESTINATION,
                    StateObject::STATE_BORDER_CROSSING,
                    StateObject::STATE_DISTANCE_TRAVELLED,
                    StateObject::STATE_GOODS_DESCRIPTION,
                    StateObject::STATE_HAZARDOUS_GOODS,
                    StateObject::STATE_CARGO_TYPE,
                    StateObject::STATE_GOODS_WEIGHT,

                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'origin_to_destination' => [
                        'from' => StateObject::STATE_ORIGIN,
                        'to' =>  StateObject::STATE_DESTINATION,
                    ],
                    'destination_to_border_crossing_ni_only' => [
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_BORDER_CROSSING,
                        'guard' => 'subject.getSubject().getDay().getResponse().getSurvey().getIsNorthernIreland()',
                    ],
                    'destination_to_distance_travelled' => [
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_DISTANCE_TRAVELLED,
                        'guard' => 'is_empty(subject.getSubject().getId())
                            && !subject.getSubject().getDay().getResponse().getSurvey().getIsNorthernIreland()',
                    ],
                    'border_crossing_to_distance_travelled' => [
                        'from' =>  StateObject::STATE_BORDER_CROSSING,
                        'to' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'distance_travelled_to_goods_description' => [
                        'from' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'to' =>  StateObject::STATE_GOODS_DESCRIPTION,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'goods_description_empty' => [
                        'name' => 'finish',
                        'from' =>  StateObject::STATE_GOODS_DESCRIPTION,
                        'to' =>  StateObject::STATE_END,
                        'guard' => 'is_empty(subject.getSubject().getId())
                            && subject.getSubject().isGoodsDescriptionEmptyOption()',
                        'metadata' => [
                            'persist' => true,
                            'submitLabel' => 'Continue',
                            'redirectRoute' => [
                                'routeName' => 'app_domesticsurvey_day_view',
                                'parameterMappings' => ['dayNumber' => 'day.number'],
                            ],
                        ],
                    ],
                    'goods_description_to_hazardous_goods' => [
                        'from' =>  StateObject::STATE_GOODS_DESCRIPTION,
                        'to' => StateObject::STATE_HAZARDOUS_GOODS,
                        'guard' => '!subject.getSubject().isGoodsDescriptionEmptyOption()',
                    ],
                    'hazardous_goods_to_cargo_type' => [
                        'from' => StateObject::STATE_HAZARDOUS_GOODS,
                        'to' => StateObject::STATE_CARGO_TYPE,
                    ],
                    'cargo_type_to_goods_weight' => [
                        'from' => StateObject::STATE_CARGO_TYPE,
                        'to' => StateObject::STATE_GOODS_WEIGHT,
                    ],

                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => 'app_domesticsurvey_day_view',
                                'parameterMappings' => ['dayNumber' => 'day.number'],
                            ],
                        ],
                        'from' => StateObject::STATE_GOODS_WEIGHT,
                        'to' =>  StateObject::STATE_END,
                    ],

                    // The next two states are the possible end points of changing locations sub-wizard
                    // Destination (GB) and Edit border crossing (NI)
                    'edit_origin_and_destination_gb_only' => [
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())
                            && !subject.getSubject().getDay().getResponse().getSurvey().getIsNorthernIreland()',
                        'metadata' => $editEndMetadata,
                    ],
                    'edit_border_crossing' => [
                        'from' =>  StateObject::STATE_BORDER_CROSSING,
                        'to' => StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())',
                        'metadata' => $editEndMetadata,
                    ],


                    // Edit distance travelled
                    'edit_distance_travelled' => [
                        'from' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'to' =>  StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())',
                        'metadata' => $editEndMetadata,
                    ],


                    // Editing goods description/haxardous/cargo
                    'edit_goods_description_empty' => [
                        'from' =>  StateObject::STATE_GOODS_DESCRIPTION,
                        'to' =>  StateObject::STATE_END,
                        'guard' => '!is_empty(subject.getSubject().getId())
                            && subject.getSubject().isGoodsDescriptionEmptyOption()',
                        'metadata' => array_merge($editEndMetadata, [
                            'submitLabel' => 'Continue',
                        ]),
                    ],

                ]
            ],
        ],
    ]);
};