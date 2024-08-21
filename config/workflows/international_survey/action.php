<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\AbstractActionController;
use App\Controller\InternationalSurvey\ActionController;
use App\Controller\InternationalSurvey\TripController;
use App\Workflow\InternationalSurvey\ActionState as StateObject;

return static function (ContainerConfigurator $container) {
    $expressionEditing = 'subject.getSubject().getId()';
    $expressionCreating = '!subject.getSubject().getId()';

    $defaultEditingConfig = [
        'to' => StateObject::STATE_END,
        'guard' => $expressionEditing,
        'metadata' => [
            'redirectRoute' => [
                'routeName' => 'app_internationalsurvey_action_view',
                'parameterMappings' => [
                    'actionId' => 'id',
                ],
            ],
            'persist' => true,
        ],
    ];

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
        // Loading
                    'goods_loaded' => [
                        'from' => StateObject::STATE_PLACE,
                        'to' => StateObject::STATE_GOODS_DESCRIPTION,
                        'guard' => "{$expressionCreating} && subject.getSubject().getLoading()",
                    ],
                    'goods_description_entered' => [
                        'from' => StateObject::STATE_GOODS_DESCRIPTION,
                        'to' => StateObject::STATE_WEIGHT_LOADED,
                    ],
                    'weight_of_goods_entered' => [
                        'from' => StateObject::STATE_WEIGHT_LOADED,
                        'to' => StateObject::STATE_HAZARDOUS_GOODS,
                        'guard' => $expressionCreating,
                    ],
                    'hazardous_goods_entered' => [
                        'from' => StateObject::STATE_HAZARDOUS_GOODS,
                        'to' => StateObject::STATE_CARGO_TYPE,
                    ],
                    'cargo_type_entered' => [
                        'from' => StateObject::STATE_CARGO_TYPE,
                        'to' => StateObject::STATE_ADD_ANOTHER,
                        'guard' => $expressionCreating,
                        'metadata' => [
                            'persist' => true,
                        ],
                    ],

        // Edit loaded
                    'finish_edit_goods_loaded' => array_merge($defaultEditingConfig, [
                        'from' => StateObject::STATE_PLACE,
                        'guard' => "{$expressionEditing} && subject.getSubject().getLoading()",
                    ]),
                    'finish_edit_weight_loaded' => array_merge($defaultEditingConfig, [
                        'from' => StateObject::STATE_WEIGHT_LOADED,
                        'guard' => $expressionEditing,
                    ]),
                    'finish_edit_loaded' => array_merge($defaultEditingConfig, [
                        'from' => StateObject::STATE_CARGO_TYPE,
                    ]),


        // Unloading
                    'goods_unloaded' => [
                        'from' => StateObject::STATE_PLACE,
                        'to' => StateObject::STATE_CONSIGNMENT_UNLOADED,
                        'guard' => "{$expressionCreating} && !subject.getSubject().getLoading()",
                    ],
                    'unloaded_consignment_selected' => [
                        'from' => StateObject::STATE_CONSIGNMENT_UNLOADED,
                        'to' => StateObject::STATE_WEIGHT_UNLOADED,
                    ],
                    'unloaded_weight_entered' => [
                        'from' => StateObject::STATE_WEIGHT_UNLOADED,
                        'to' => StateObject::STATE_ADD_ANOTHER,
                        'guard' => $expressionCreating,
                        'metadata' => [
                            'persist' => true,
                        ],
                    ],


        // Edit unload
                    'finish_edit_goods_unloaded' => array_merge($defaultEditingConfig, [
                        'from' => StateObject::STATE_PLACE,
                        'guard' => "{$expressionEditing} && !subject.getSubject().getLoading()",
                    ]),
                    'finish_edit_unloaded' => array_merge($defaultEditingConfig, [
                        'from' => StateObject::STATE_WEIGHT_UNLOADED,
                    ]),


        // Add another
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
                                'property' => 'confirm',
                                'value' => false,
                            ],
                        ],
                    ],
                    'finish_add_another' => [
                        'from' => StateObject::STATE_ADD_ANOTHER,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'redirectRoute' => [
                                'routeName' => 'app_internationalsurvey_action_add_another',
                                'parameterMappings' => [
                                    'tripId' => 'trip.id',
                                ],
                            ],
                            'transitionWhenFormData' => [
                                'property' => 'confirm',
                                'value' => true,
                            ],
                        ],
                    ],
                ]
            ],
        ],
    ]);
};