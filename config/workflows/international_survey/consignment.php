<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\ConsignmentController;
use App\Controller\InternationalSurvey\ConsignmentWorkflowController;
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
                    'hazardous_goods_changed' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => ConsignmentController::SUMMARY_ROUTE,
                                'parameterMappings' => [
                                    'tripId' => 'trip.id',
                                    'consignmentId' => 'id',
                                ],
                            ],
                        ],
                        'from' => StateObject::STATE_HAZARDOUS_GOODS,
                        'to' => StateObject::STATE_END,
                    ],
                    'cargo_type_entered' => [
                        'from' => StateObject::STATE_CARGO_TYPE,
                        'to' => StateObject::STATE_WEIGHT_OF_GOODS,
                    ],
                    'cargo_type_changed' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => ConsignmentController::SUMMARY_ROUTE,
                                'parameterMappings' => [
                                    'tripId' => 'trip.id',
                                    'consignmentId' => 'id',
                                ],
                            ],
                        ],
                        'from' => StateObject::STATE_CARGO_TYPE,
                        'to' => StateObject::STATE_END,
                    ],
                    'weight_of_goods_entered' => [
                        'from' => StateObject::STATE_WEIGHT_OF_GOODS,
                        'to' => StateObject::STATE_PLACE_OF_LOADING,
                    ],
                    'weight_of_goods_changed' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => ConsignmentController::SUMMARY_ROUTE,
                                'parameterMappings' => [
                                    'tripId' => 'trip.id',
                                    'consignmentId' => 'id',
                                ],
                            ],
                        ],
                        'from' => StateObject::STATE_WEIGHT_OF_GOODS,
                        'to' => StateObject::STATE_END,
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
                    'place_of_unloading_changed' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => [
                                'routeName' => ConsignmentController::SUMMARY_ROUTE,
                                'parameterMappings' => [
                                    'tripId' => 'trip.id',
                                    'consignmentId' => 'id',
                                ],
                            ],
                        ],
                        'from' => StateObject::STATE_PLACE_OF_UNLOADING,
                        'to' => StateObject::STATE_END,
                    ],
                    'finish' => [
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => ConsignmentController::SUMMARY_ROUTE,
                                'parameterMappings' => [
                                    'tripId' => 'trip.id',
                                    'consignmentId' => 'id',
                                ],
                            ],
                            'transitionWhenFormData' => [
                                'property' => 'confirm',
                                'value' => false,
                            ],
                        ],
                        'from' => StateObject::STATE_ADD_ANOTHER,
                        'to' => StateObject::STATE_END,
                    ],
                    'add_another' => [
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => ConsignmentWorkflowController::ADD_ANOTHER_ROUTE,
                                'parameterMappings' => [
                                    'tripId' => 'trip.id',
                                    'consignmentId' => 'id',
                                ],
                            ],
                            'transitionWhenFormData' => [
                                'property' => 'confirm',
                                'value' => true,
                            ],
                        ],
                        'from' => StateObject::STATE_ADD_ANOTHER,
                        'to' => StateObject::STATE_GOODS_DESCRIPTION,
                    ],
                    'goods_change' => [
                        'from' => StateObject::STATE_END,
                        'to' => StateObject::STATE_GOODS_DESCRIPTION,
                    ],
                    'place_of_loading_change' => [
                        'from' => StateObject::STATE_END,
                        'to' => StateObject::STATE_PLACE_OF_LOADING,
                    ],
                    'place_of_unloading_change' => [
                        'from' => StateObject::STATE_END,
                        'to' => StateObject::STATE_PLACE_OF_UNLOADING,
                    ],
                    'cargo_type_change' => [
                        'from' => StateObject::STATE_END,
                        'to' => StateObject::STATE_CARGO_TYPE,
                    ],
                    'weight_of_goods_change' => [
                        'from' => StateObject::STATE_END,
                        'to' => StateObject::STATE_WEIGHT_OF_GOODS,
                    ],
                ]
            ],
        ],
    ]);
};