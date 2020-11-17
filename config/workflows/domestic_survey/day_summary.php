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
                    StateObject::STATE_DISTANCE_TRAVELLED,
                    StateObject::STATE_GOODS,
//                    StateObject::STATE_,

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
                    'destination-port-to-next' => [
                        'from' => StateObject::STATE_DESTINATION_PORTS,
                        'to' =>  StateObject::STATE_DISTANCE_TRAVELLED,
                    ],
                    'destination-to-distance-travelled' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsUnloaded', 'value' => false]],
                        'from' => StateObject::STATE_DESTINATION,
                        'to' =>  StateObject::STATE_DISTANCE_TRAVELLED,
                    ],
                    'distance-travelled-to-goods' => [
                        'from' => StateObject::STATE_DISTANCE_TRAVELLED,
                        'to' =>  StateObject::STATE_GOODS,
                    ],


                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                        ],
                        'from' => StateObject::STATE_GOODS,
                        'to' =>  StateObject::STATE_END,
                    ],
                ]
            ],
        ],
    ]);
};