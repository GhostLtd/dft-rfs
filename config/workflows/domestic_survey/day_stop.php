<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\VehicleController;
use App\Entity\Domestic\Day;
use App\Workflow\DomesticSurvey\DayStopState as StateObject;
use Doctrine\Migrations\Version\State;

return static function (ContainerConfigurator $container) {
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
                    StateObject::STATE_ORIGIN_PORTS,
                    StateObject::STATE_DESTINATION,
                    StateObject::STATE_DESTINATION_PORTS,
                    StateObject::STATE_BORDER_CROSSING,
                    StateObject::STATE_DISTANCE_TRAVELLED,
                    StateObject::STATE_GOODS_DESCRIPTION,
                    StateObject::STATE_HAZARDOUS_GOODS,
                    StateObject::STATE_CARGO_TYPE,
                    StateObject::STATE_GOODS_WEIGHT,
//                    StateObject::STATE_,

                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'goods-loaded' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsLoaded', 'value' => true]],
                        'from' => StateObject::STATE_ORIGIN,
                        'to' =>  StateObject::STATE_ORIGIN_PORTS,
                    ],
                    'origin-port-to-destination' => [
                        'from' => StateObject::STATE_ORIGIN_PORTS,
                        'to' =>  StateObject::STATE_DESTINATION,
                    ],
                    'goods-not-loaded' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsLoaded', 'value' => false]],
                        'from' => StateObject::STATE_ORIGIN,
                        'to' =>  StateObject::STATE_DESTINATION,
                    ],
                    'goods-not-unloaded-ni-only' => [
                        'metadata' => ['transitionWhenCallback' => 'transitionGoodsNotUnloadedNICallback'],
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_BORDER_CROSSING,
                    ],
                    'goods-not-unloaded-gb-only' => [
                        'metadata' => ['transitionWhenCallback' => 'transitionGoodsNotUnloadedGBCallback'],
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_DISTANCE_TRAVELLED,
                    ],
                    'goods-unloaded-gb-or-ni' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsUnloaded', 'value' => true]],
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_DESTINATION_PORTS,
                    ],
                    'goods-unloaded-ni-only' => [
                        'metadata' => ['transitionWhenCallback' => 'isNorthernIrelandSurvey'],
                        'from' => StateObject::STATE_DESTINATION_PORTS,
                        'to' =>  StateObject::STATE_BORDER_CROSSING,
                    ],
                    'border-crossing-to-distance-travelled' => [
                        'from' =>  StateObject::STATE_BORDER_CROSSING,
                        'to' => StateObject::STATE_DISTANCE_TRAVELLED,
                    ],
                    'goods-unloaded-gb-only' => [
                        'metadata' => ['transitionWhenCallbackNot' => 'isNorthernIrelandSurvey'],
                        'from' => StateObject::STATE_DESTINATION_PORTS,
                        'to' =>  StateObject::STATE_DISTANCE_TRAVELLED,
                    ],
                    'distance-travelled-to-goods-description' => [
                        'from' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'to' =>  StateObject::STATE_GOODS_DESCRIPTION,
                    ],
                    'goods-description-empty' => [
                        'name' => 'finish',
                        'metadata' => [
                            'transitionWhenCallback' => 'isGoodsDescriptionEmptyOption',
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => 'app_domesticsurvey_day_view',
                                'parameterMappings' => ['dayNumber' => 'day.number'],
                            ],
                        ],
                        'from' =>  StateObject::STATE_GOODS_DESCRIPTION,
                        'to' =>  StateObject::STATE_END,
                    ],
                    'goods-description-to-hazardous-goods' => [
                        'metadata' => ['transitionWhenCallbackNot' => 'isGoodsDescriptionEmptyOption'],
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
                ]
            ],
        ],
    ]);
};