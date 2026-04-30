<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\DomesticSurvey\DaySummary\BorderCrossingType;
use App\Form\DomesticSurvey\DaySummary\CargoTypeType;
use App\Form\DomesticSurvey\DaySummary\DestinationType;
use App\Form\DomesticSurvey\DaySummary\DistanceTravelledType;
use App\Form\DomesticSurvey\DaySummary\FurthestStopType;
use App\Form\DomesticSurvey\DaySummary\GoodsDescriptionType;
use App\Form\DomesticSurvey\DaySummary\GoodsWeightType;
use App\Form\DomesticSurvey\DaySummary\HazardousGoodsType;
use App\Form\DomesticSurvey\DaySummary\NumberOfStopsType;
use App\Form\DomesticSurvey\DaySummary\OriginType;
use App\Workflow\DomesticSurvey\DaySummaryState as StateObject;

return static function (ContainerConfigurator $container) {
    $editEndMetadata = [
        'persist' => true,
        'redirect_route' => [
            'name' => 'app_domesticsurvey_day_view',
            'parameters' => ['dayNumber' => 'subject.day.number'],
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_day_summary' => [
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
                            'template' => 'domestic_survey/day_summary/intro.html.twig',
                            'is_valid_alternative_start_place' => 'is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_ORIGIN,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-origin.html.twig',
                            'form_class' => OriginType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DESTINATION,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-destination.html.twig',
                            'form_class' => DestinationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_BORDER_CROSSING,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-border-crossing.html.twig',
                            'form_class' => BorderCrossingType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-distance-travelled.html.twig',
                            'form_class' => DistanceTravelledType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_FURTHEST_STOP,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-furthest-stop.html.twig',
                            'form_class' => FurthestStopType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_GOODS_DESCRIPTION,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-goods-description.html.twig',
                            'form_class' => GoodsDescriptionType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_HAZARDOUS_GOODS,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-hazardous-goods.html.twig',
                            'form_class' => HazardousGoodsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CARGO_TYPE,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-cargo-type.html.twig',
                            'form_class' => CargoTypeType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_GOODS_WEIGHT,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-goods-weight.html.twig',
                            'form_class' => GoodsWeightType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_NUMBER_OF_STOPS,
                        'metadata' => [
                            'template' => 'domestic_survey/day_summary/form-number-of-stops.html.twig',
                            'form_class' => NumberOfStopsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_END,
                    ],
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
                            'redirect_route' => [
                                'name' => 'app_domesticsurvey_day_view',
                                'parameters' => ['dayNumber' => 'subject.day.number'],
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