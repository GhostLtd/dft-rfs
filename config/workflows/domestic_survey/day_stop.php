<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\DomesticSurvey\DayStop\BorderCrossingType;
use App\Form\DomesticSurvey\DayStop\CargoTypeType;
use App\Form\DomesticSurvey\DayStop\DestinationType;
use App\Form\DomesticSurvey\DayStop\DistanceTravelledType;
use App\Form\DomesticSurvey\DayStop\GoodsDescriptionType;
use App\Form\DomesticSurvey\DayStop\GoodsWeightType;
use App\Form\DomesticSurvey\DayStop\HazardousGoodsType;
use App\Form\DomesticSurvey\DayStop\OriginType;
use App\Workflow\DomesticSurvey\DayStopState as StateObject;

return static function (ContainerConfigurator $container) {
    $editEndMetadata =  [
        'persist' => true,
        'redirect_route' => [
            'name' => 'app_domesticsurvey_day_view',
            'parameters' => ['dayNumber' => 'subject.day.number'],
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_day_stop' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_ORIGIN,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_INTRO,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/intro.html.twig',
                            'is_valid_alternative_start_place' => 'is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_ORIGIN,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/form-origin.html.twig',
                            'form_class' => OriginType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DESTINATION,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/form-destination.html.twig',
                            'form_class' => DestinationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_BORDER_CROSSING,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/form-border-crossing.html.twig',
                            'form_class' => BorderCrossingType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/form-distance-travelled.html.twig',
                            'form_class' => DistanceTravelledType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_GOODS_DESCRIPTION,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/form-goods-description.html.twig',
                            'form_class' => GoodsDescriptionType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_HAZARDOUS_GOODS,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/form-hazardous-goods.html.twig',
                            'form_class' => HazardousGoodsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CARGO_TYPE,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/form-cargo-type.html.twig',
                            'form_class' => CargoTypeType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_GOODS_WEIGHT,
                        'metadata' => [
                            'template' => 'domestic_survey/day_stop/form-goods-weight.html.twig',
                            'form_class' => GoodsWeightType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId()) and state.getSubject().getGoodsDescription() !== "empty"',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_END,
                    ],
                ],
                'transitions' => [
                    'intro_to_origin' => [
                        'from' =>  StateObject::STATE_INTRO,
                        'to' => StateObject::STATE_ORIGIN,
                    ],
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
                            'submit_label' => 'Continue',
                            'redirect_route' => [
                                'name' => 'app_domesticsurvey_day_view',
                                'parameters' => ['dayNumber' => 'subject.day.number'],
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
                            'redirect_route' => [
                                'name' => 'app_domesticsurvey_day_view',
                                'parameters' => ['dayNumber' => 'subject.day.number'],
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
                            'submit_label' => 'Continue',
                        ]),
                    ],

                ]
            ],
        ],
    ]);
};