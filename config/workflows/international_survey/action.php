<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\TripController;
use App\Form\InternationalSurvey\Action\AddAnotherType;
use App\Form\InternationalSurvey\Action\CargoTypeType;
use App\Form\InternationalSurvey\Action\GoodsDescriptionType;
use App\Form\InternationalSurvey\Action\GoodsLoadedWeightType;
use App\Form\InternationalSurvey\Action\GoodsUnloadedWeightType;
use App\Form\InternationalSurvey\Action\HazardousGoodsType;
use App\Form\InternationalSurvey\Action\LoadingPlaceType;
use App\Form\InternationalSurvey\Action\PlaceType;
use App\Workflow\InternationalSurvey\ActionState as StateObject;

return static function (ContainerConfigurator $container) {
    $expressionEditing = 'subject.getSubject().getId()';
    $expressionCreating = '!subject.getSubject().getId()';

    $defaultEditingConfig = [
        'to' => StateObject::STATE_END,
        'guard' => $expressionEditing,
        'metadata' => [
            'redirect_route' => [
                'name' => 'app_internationalsurvey_action_view',
                'parameters' => [
                    'actionId' => 'subject.id',
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
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_PLACE,
                        'metadata' => [
                            'template' => 'international_survey/action/form-place.html.twig',
                            'form_class' => PlaceType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_GOODS_DESCRIPTION,
                        'metadata' => [
                            'template' => 'international_survey/action/form-goods-description.html.twig',
                            'form_class' => GoodsDescriptionType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId()) and state.getSubject().getLoading()',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_HAZARDOUS_GOODS,
                        'metadata' => [
                            'template' => 'international_survey/action/form-hazardous-goods.html.twig',
                            'form_class' => HazardousGoodsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId()) and state.getSubject().getLoading()',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CARGO_TYPE,
                        'metadata' => [
                            'template' => 'international_survey/action/form-cargo-type.html.twig',
                            'form_class' => CargoTypeType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_WEIGHT_LOADED,
                        'metadata' => [
                            'template' => 'international_survey/action/form-weight-loaded.html.twig',
                            'form_class' => GoodsLoadedWeightType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CONSIGNMENT_UNLOADED,
                        'metadata' => [
                            'template' => 'international_survey/action/form-unloaded.html.twig',
                            'form_class' => LoadingPlaceType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId()) and !state.getSubject().getLoading()',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_WEIGHT_UNLOADED,
                        'metadata' => [
                            'template' => 'international_survey/action/form-weight-unloaded.html.twig',
                            'form_class' => GoodsUnloadedWeightType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_ADD_ANOTHER,
                        'metadata' => [
                            'template' => 'international_survey/action/form-add-another.html.twig',
                            'form_class' => AddAnotherType::class,
                            'form_data_property' => null,
                            // Add-another needs to be a jump-in for new actions because the session is cleared
                            // while rendering the add-another page.
                            'is_valid_alternative_start_place' => 'is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_END,
                    ],
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
                        'guard' => 'subject.getConfirm() === false',
                        'metadata' => [
                            'redirect_route' => [
                                'name' => TripController::TRIP_ROUTE,
                                'parameters' => [
                                    'id' => 'subject.trip.id',
                                ],
                            ],
                        ],
                    ],
                    'finish_add_another' => [
                        'from' => StateObject::STATE_ADD_ANOTHER,
                        'to' => StateObject::STATE_END,
                        'guard' => 'subject.getConfirm() === true',
                        'metadata' => [
                            'redirect_route' => [
                                'name' => 'app_internationalsurvey_action_add_another',
                                'parameters' => [
                                    'tripId' => 'subject.trip.id',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
};
