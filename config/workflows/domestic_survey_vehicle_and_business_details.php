<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\DomesticSurveyResponse;
use App\Workflow\DomesticSurveyInitialDetailsState;
use App\Workflow\DomesticSurveyVehicleAndBusinessDetailsState as StateObject;

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
                    StateObject::STATE_VEHICLE_WEIGHTS_AND_FUEL,
                    StateObject::STATE_VEHICLE_TRAILER_CONFIGURATION,
                    StateObject::STATE_VEHICLE_AXLE_CONFIGURATION,
                    StateObject::STATE_VEHICLE_BODY,
                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'business-details-to-weights' => [
                        'from' => StateObject::STATE_BUSINESS_DETAILS,
                        'to' =>  StateObject::STATE_VEHICLE_WEIGHTS_AND_FUEL,
                    ],
                    'weights-to-trailer-config' => [
                        'from' => StateObject::STATE_VEHICLE_WEIGHTS_AND_FUEL,
                        'to' =>  StateObject::STATE_VEHICLE_TRAILER_CONFIGURATION,
                    ],
                    'trailer-config-to-axle-config' => [
                        'from' => StateObject::STATE_VEHICLE_TRAILER_CONFIGURATION,
                        'to' =>  StateObject::STATE_VEHICLE_AXLE_CONFIGURATION,
                    ],
                    'config-to-body-type' => [
                        'from' => StateObject::STATE_VEHICLE_AXLE_CONFIGURATION,
                        'to' =>  StateObject::STATE_VEHICLE_BODY,
                    ],

                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                        ],
                        'from' => StateObject::STATE_VEHICLE_BODY,
                        'to' =>  StateObject::STATE_END,
                    ],
                ]
            ],
        ],
    ]);
};