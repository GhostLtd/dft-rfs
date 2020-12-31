<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\ActionAddController;
use App\Controller\InternationalSurvey\TripController;
use App\Workflow\InternationalSurvey\ActionState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_action' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_PLACE,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_GOODS_DESCRIPTION,
                    StateObject::STATE_HAZARDOUS_GOODS,
                    StateObject::STATE_CARGO_TYPE,
                    StateObject::STATE_WEIGHT_LOADED,
                    StateObject::STATE_PLACE,
                    StateObject::STATE_CONSIGNMENT_UNLOADED,
                    StateObject::STATE_WEIGHT_UNLOADED,
                    StateObject::STATE_ADD_ANOTHER,
                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'goods_loaded' => [
                        'from' => StateObject::STATE_PLACE,
                        'to' => StateObject::STATE_GOODS_DESCRIPTION,
                    ],
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
                        'to' => StateObject::STATE_WEIGHT_LOADED,
                    ],
                    'weight_of_goods_entered' => [
                        'from' => StateObject::STATE_WEIGHT_LOADED,
                        'to' => StateObject::STATE_ADD_ANOTHER,
                        'metadata' => [
                            'persist' => true,
                        ],
                    ],

                    'finish_edit_loaded' => [
                        'from' => StateObject::STATE_WEIGHT_LOADED,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => [
                                    'id' => 'trip.id',
                                ],
                            ],
                            'persist' => true,
                        ],
                    ],

                    'goods_unloaded' => [
                        'from' => StateObject::STATE_PLACE,
                        'to' => StateObject::STATE_CONSIGNMENT_UNLOADED
                    ],
                    'unloaded_consignment_selected' => [
                        'from' => StateObject::STATE_CONSIGNMENT_UNLOADED,
                        'to' => StateObject::STATE_WEIGHT_UNLOADED,
                    ],
                    'unloaded_weight_entered' => [
                        'from' => StateObject::STATE_WEIGHT_UNLOADED,
                        'to' => StateObject::STATE_ADD_ANOTHER,
                        'metadata' => [
                            'persist' => true,
                        ],
                    ],

                    'finish_edit_unloaded' => [
                        'from' => StateObject::STATE_WEIGHT_UNLOADED,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => [
                                    'id' => 'trip.id',
                                ],
                            ],
                            'persist' => true,
                        ],
                    ],

                    'finish_dont_add_another' => [
                        'from' => StateObject::STATE_ADD_ANOTHER,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => TripController::TRIP_ROUTE,
                                'parameterMappings' => [
                                    'id' => 'trip.id',
                                ],
                            ],
                            'transitionWhenFormData' => [
                                'property' => 'add_another',
                                'value' => false,
                            ],
                        ],
                    ],
                    'finish_add_another' => [
                        'from' => StateObject::STATE_ADD_ANOTHER,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => ActionAddController::ADD_ANOTHER_ROUTE,
                                'parameterMappings' => [
                                    'tripId' => 'trip.id',
                                ],
                            ],
                            'transitionWhenFormData' => [
                                'property' => 'add_another',
                                'value' => true,
                            ],
                        ],
                    ],
                ]
            ],
        ],
    ]);
};