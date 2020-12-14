<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\InternationalSurvey\ConsignmentState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_consignment' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_GOODS_DESCRIPTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_GOODS_DESCRIPTION,
                    StateObject::STATE_HAZARDOUS_GOODS,
                    StateObject::STATE_CARGO_TYPE,
                    StateObject::STATE_WEIGHT_OF_GOODS,
                    StateObject::STATE_PLACE_OF_LOADING,
                    StateObject::STATE_PLACE_OF_UNLOADING,
                    StateObject::STATE_ADD_ANOTHER,
                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'goods_description_entered' => [
                        'from' => StateObject::STATE_GOODS_DESCRIPTION,
                        'to' => StateObject::STATE_HAZARDOUS_GOODS,
                    ],
                    'hazardous_goods_entered' => [
                        'from' => StateObject::STATE_HAZARDOUS_GOODS,
                        'to' => StateObject::STATE_CARGO_TYPE,
                    ],
                    'cargo_type_entered' => [
                        'from' => StateObject::STATE_CARGO_TYPE,
                        'to' => StateObject::STATE_WEIGHT_OF_GOODS,
                    ],
                    'weight_of_goods_entered' => [
                        'from' => StateObject::STATE_WEIGHT_OF_GOODS,
                        'to' => StateObject::STATE_PLACE_OF_LOADING,
                    ],
                    'place_of_loading_entered' => [
                        'from' => StateObject::STATE_PLACE_OF_LOADING,
                        'to' => StateObject::STATE_PLACE_OF_UNLOADING,
                    ],
                    'place_of_unloading_entered' => [
                        'metadata' => [
                            'persist' => true,
                        ],
                        'from' => StateObject::STATE_PLACE_OF_UNLOADING,
                        'to' => StateObject::STATE_ADD_ANOTHER,
                    ],
                    'finish' => [
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => '',
                                'parameterMappings' => [],
                            ],
                        ],
                        'from' => StateObject::STATE_ADD_ANOTHER,
                        'to' => StateObject::STATE_END,
                    ],
                    'add_another' => [
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => '',
                                'parameterMappings' => [],
                            ],
                        ],
                        'from' => StateObject::STATE_ADD_ANOTHER,
                        'to' => StateObject::STATE_GOODS_DESCRIPTION,
                    ],
                ]
            ],
        ],
    ]);
};