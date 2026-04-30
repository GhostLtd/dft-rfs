<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\DomesticSurvey\IndexController;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\BusinessDetailsType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleAxleConfigurationType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleBodyType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleTrailerConfigurationType;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleWeightsType;
use App\Workflow\DomesticSurvey\VehicleAndBusinessDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_vehicle_and_business_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_BUSINESS_DETAILS,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_BUSINESS_DETAILS,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-business-details.html.twig',
                            'form_class' => BusinessDetailsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_BUSINESS_DETAILS,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-business-details.html.twig',
                            'form_class' => BusinessDetailsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_VEHICLE_WEIGHTS,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-vehicle-weights.html.twig',
                            'form_class' => VehicleWeightsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_WEIGHTS,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-vehicle-weights.html.twig',
                            'form_class' => VehicleWeightsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_VEHICLE_TRAILER_CONFIGURATION,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-vehicle-trailer-configuration.html.twig',
                            'form_class' => VehicleTrailerConfigurationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-vehicle-trailer-configuration.html.twig',
                            'form_class' => VehicleTrailerConfigurationType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_VEHICLE_AXLE_CONFIGURATION,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-vehicle-axle-configuration.html.twig',
                            'form_class' => VehicleAxleConfigurationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-vehicle-axle-configuration.html.twig',
                            'form_class' => VehicleAxleConfigurationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_VEHICLE_BODY,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-vehicle-body.html.twig',
                            'form_class' => VehicleBodyType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_BODY,
                        'metadata' => [
                            'template' => 'domestic_survey/vehicle_and_business_details/form-vehicle-body.html.twig',
                            'form_class' => VehicleBodyType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_END,
                    ],
                ],
                'transitions' => [
                    'business-details-to-trailer-config' => [
                        'from' => StateObject::STATE_BUSINESS_DETAILS,
                        'to' =>  StateObject::STATE_VEHICLE_TRAILER_CONFIGURATION,
                    ],
//                    'weights-to-trailer-config' => [
//                        'from' => StateObject::STATE_VEHICLE_WEIGHTS,
//                        'to' =>  StateObject::STATE_VEHICLE_TRAILER_CONFIGURATION,
//                    ],
                    'trailer-config-to-axle-config' => [
                        'from' => StateObject::STATE_VEHICLE_TRAILER_CONFIGURATION,
                        'to' =>  StateObject::STATE_VEHICLE_AXLE_CONFIGURATION,
                    ],
                    'axle-config-to-body-type' => [
                        'from' => StateObject::STATE_VEHICLE_AXLE_CONFIGURATION,
                        'to' =>  StateObject::STATE_VEHICLE_BODY,
                    ],
                    'body-type-to-weights' => [
                        'from' => StateObject::STATE_VEHICLE_BODY,
                        'to' =>  StateObject::STATE_VEHICLE_WEIGHTS,
                    ],

                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirect_route' => IndexController::SUMMARY_ROUTE,
                        ],
                        'from' => StateObject::STATE_VEHICLE_WEIGHTS,
                        'to' =>  StateObject::STATE_END,
                    ],


                    'change-vehicle-config' => [
                        'from' =>  StateObject::STATE_END,
                        'to' =>  StateObject::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
                    ],
                    'change-trailer-config-to-axle-config' => [
                        'from' => StateObject::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
                        'to' =>  StateObject::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION,
                    ],
                    'change-config-to-body-type' => [
                        'from' => StateObject::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION,
                        'to' =>  StateObject::STATE_CHANGE_VEHICLE_BODY,
                    ],


                    'change-business-details' => [
                        'from' =>  StateObject::STATE_END,
                        'to' =>  StateObject::STATE_CHANGE_BUSINESS_DETAILS,
                    ],
                    'change-vehicle-weights' => [
                        'from' =>  StateObject::STATE_END,
                        'to' =>  StateObject::STATE_CHANGE_VEHICLE_WEIGHTS,
                    ],

                    'finish-2' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirect_route' => 'app_domesticsurvey_contactdetails',
                        ],
                        'from' => [StateObject::STATE_CHANGE_VEHICLE_BODY, StateObject::STATE_CHANGE_BUSINESS_DETAILS, StateObject::STATE_CHANGE_VEHICLE_WEIGHTS],
                        'to' =>  StateObject::STATE_END,
                    ],

                ]
            ],
        ],
    ]);
};