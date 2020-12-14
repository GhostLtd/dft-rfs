<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\DomesticSurvey\DaySummaryState as StateObject;

return static function (ContainerConfigurator $container) {
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
                    StateObject::STATE_ORIGIN,
                    StateObject::STATE_ORIGIN_PORTS,
                    StateObject::STATE_DESTINATION,
                    StateObject::STATE_DESTINATION_PORTS,
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
                    'origin-to-origin-port' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsLoaded', 'value' => true]],
                        'from' => StateObject::STATE_ORIGIN,
                        'to' =>  StateObject::STATE_ORIGIN_PORTS,
                    ],
                    'origin-port-to-destination' => [
                        'from' => StateObject::STATE_ORIGIN_PORTS,
                        'to' =>  StateObject::STATE_DESTINATION,
                    ],
                    'origin-to-destination' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsLoaded', 'value' => false]],
                        'from' => StateObject::STATE_ORIGIN,
                        'to' =>  StateObject::STATE_DESTINATION,
                    ],
                    'destination-to-destination-port' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsUnloaded', 'value' => true]],
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_DESTINATION_PORTS,
                    ],
                    'destination-port-to-furthest-stop' => [
                        'from' => StateObject::STATE_DESTINATION_PORTS,
                        'to' =>  StateObject::STATE_FURTHEST_STOP,
                    ],
                    'destination-to-furthest-stop' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsUnloaded', 'value' => false]],
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_FURTHEST_STOP,
                    ],
                    'furthest-stop-to-border-crossing' => [
                        'metadata' => ['transitionWhenCallback' => 'isNorthernIrelandSurvey'],
                        'from' => StateObject::STATE_FURTHEST_STOP,
                        'to' =>  StateObject::STATE_BORDER_CROSSING,
                    ],
                    'border-crossing-to-distance-travelled' => [
                        'from' =>  StateObject::STATE_BORDER_CROSSING,
                        'to' => StateObject::STATE_DISTANCE_TRAVELLED,
                    ],
                    'furthest-stop-to-distance-travelled' => [
                        'metadata' => ['transitionWhenCallbackNot' => 'isNorthernIrelandSurvey'],
                        'from' => StateObject::STATE_FURTHEST_STOP,
                        'to' => StateObject::STATE_DISTANCE_TRAVELLED,
                    ],
                    'distance-travelled-to-goods-description' => [
                        'from' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'to' =>  StateObject::STATE_GOODS_DESCRIPTION,
                    ],
                    'goods-description-to-hazardous-goods' => [
                        'from' =>  StateObject::STATE_GOODS_DESCRIPTION,
                        'to' => StateObject::STATE_HAZARDOUS_GOODS,
                    ],
                    'hazardous-goods-to-cargo-type' => [
                        'from' => StateObject::STATE_HAZARDOUS_GOODS,
                        'to' => StateObject::STATE_CARGO_TYPE,
                    ],
                    'cargo-type-to-goods-weight' => [
                        'from' => StateObject::STATE_CARGO_TYPE,
                        'to' => StateObject::STATE_GOODS_WEIGHT,
                    ],
                    'goods-weight-to-number-of-stops' => [
                        'from' => StateObject::STATE_GOODS_WEIGHT,
                        'to' => StateObject::STATE_NUMBER_OF_STOPS,
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
                ]
            ],
        ],
    ]);
};