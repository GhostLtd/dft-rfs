<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\DomesticSurvey\IndexController;
use App\Workflow\DomesticSurvey\VehicleAndBusinessDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_vehicle_and_business_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_BUSINESS_DETAILS,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_BUSINESS_DETAILS,
                    StateObject::STATE_CHANGE_BUSINESS_DETAILS,
                    StateObject::STATE_VEHICLE_WEIGHTS,
                    StateObject::STATE_CHANGE_VEHICLE_WEIGHTS,
                    StateObject::STATE_VEHICLE_TRAILER_CONFIGURATION,
                    StateObject::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
                    StateObject::STATE_VEHICLE_AXLE_CONFIGURATION,
                    StateObject::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION,
                    StateObject::STATE_VEHICLE_BODY,
                    StateObject::STATE_CHANGE_VEHICLE_BODY,
                    StateObject::STATE_END,
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
                            'redirectRoute' => IndexController::SUMMARY_ROUTE,
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
                            'redirectRoute' => 'app_domesticsurvey_contactdetails',
                        ],
                        'from' => [StateObject::STATE_CHANGE_VEHICLE_BODY, StateObject::STATE_CHANGE_BUSINESS_DETAILS, StateObject::STATE_CHANGE_VEHICLE_WEIGHTS],
                        'to' =>  StateObject::STATE_END,
                    ],

                ]
            ],
        ],
    ]);
};