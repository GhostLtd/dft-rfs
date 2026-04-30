<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\IndexController;
use App\Controller\InternationalSurvey\VehicleController;
use App\Form\InternationalSurvey\Vehicle\ConfirmDatesType;
use App\Form\InternationalSurvey\Vehicle\VehicleAxleConfigurationType;
use App\Form\InternationalSurvey\Vehicle\VehicleBodyType;
use App\Form\InternationalSurvey\Vehicle\VehicleDetailsType;
use App\Form\InternationalSurvey\Vehicle\VehicleTrailerConfigurationType;
use App\Form\InternationalSurvey\Vehicle\VehicleWeightType;
use App\Workflow\InternationalSurvey\VehicleState as StateObject;

return static function (ContainerConfigurator $container) {
    $finishMetadata = [
        'persist' => true,
        'redirect_route' => [
            'name' => VehicleController::VEHICLE_ROUTE,
            'parameters' => ['vehicleId' => 'subject.id'],
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'international_survey_vehicle' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_REQUEST_VEHICLE_DETAILS,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_REQUEST_VEHICLE_DETAILS,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-vehicle-registration.html.twig',
                            'form_class' => VehicleDetailsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_CONFIRM_DATES,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-confirm-dates.html.twig',
                            'form_class' => ConfirmDatesType::class,
                            'form_data_property' => null,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-trailer-configuration.html.twig',
                            'form_class' => VehicleTrailerConfigurationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-axle-configuration.html.twig',
                            'form_class' => VehicleAxleConfigurationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_VEHICLE_BODY,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-vehicle-body.html.twig',
                            'form_class' => VehicleBodyType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_VEHICLE_WEIGHT,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-vehicle-weight.html.twig',
                            'form_class' => VehicleWeightType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_DETAILS,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-vehicle-registration.html.twig',
                            'form_class' => VehicleDetailsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-trailer-configuration.html.twig',
                            'form_class' => VehicleTrailerConfigurationType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-axle-configuration.html.twig',
                            'form_class' => VehicleAxleConfigurationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_BODY,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-vehicle-body.html.twig',
                            'form_class' => VehicleBodyType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_VEHICLE_WEIGHT,
                        'metadata' => [
                            'template' => 'international_survey/vehicle/form-vehicle-weight.html.twig',
                            'form_class' => VehicleWeightType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_SUMMARY,
                    ],
                ],
                'transitions' => [
                    'vehicle details entered' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_DETAILS,
                        'to' => StateObject::STATE_REQUEST_CONFIRM_DATES,
                    ],
                    'dates confirmed' => [
                        'from' => StateObject::STATE_REQUEST_CONFIRM_DATES,
                        'to' => StateObject::STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION,
                        'guard' => 'subject.getConfirm() === true',
                    ],
                    'dates rejected' => [
                        'from' => StateObject::STATE_REQUEST_CONFIRM_DATES,
                        'to' => StateObject::STATE_SUMMARY,
                        'guard' => 'subject.getConfirm() === false',
                        'metadata' => [
                            'redirect_route' => IndexController::SUMMARY_ROUTE,
                            'notification_banner' => [
                                'title' => 'international.vehicle.confirm-dates.notification-banner.title',
                                'heading' => 'international.vehicle.confirm-dates.notification-banner.heading',
                                'content' => 'international.vehicle.confirm-dates.notification-banner.content',
                                'translation_parameters' => [
                                    'registrationMark' => 'subject.registrationMark',
                                ],
                            ],
                        ],
                    ],
                    'vehicle trailer entered' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION,
                        'to' => StateObject::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION,
                    ],
                    'axle configuration entered' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION,
                        'to' => StateObject::STATE_REQUEST_VEHICLE_BODY,
                    ],
                    'vehicle body entered' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_BODY,
                        'to' => StateObject::STATE_REQUEST_VEHICLE_WEIGHT,
                    ],
                    'finish' => [
                        'from' => StateObject::STATE_REQUEST_VEHICLE_WEIGHT,
                        'to' => StateObject::STATE_SUMMARY,
                        'metadata' => $finishMetadata,
                    ],
                    'change vehicle details' => [
                        'from' => StateObject::STATE_SUMMARY,
                        'to' => StateObject::STATE_CHANGE_VEHICLE_DETAILS,
                    ],
                    'change vehicle details save' => [
                        'name' => 'finish',
                        'from' => StateObject::STATE_CHANGE_VEHICLE_DETAILS,
                        'to' => StateObject::STATE_SUMMARY,
                        'metadata' => $finishMetadata,
                    ],
                    'change trailer configuration' => [
                        'from' => StateObject::STATE_SUMMARY,
                        'to' => StateObject::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
                    ],
                    'change axle configuration' => [
                        'from' => StateObject::STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION,
                        'to' => StateObject::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION,
                    ],
                    'changed configuration' => [
                        'name' => 'finish',
                        'from' => StateObject::STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION,
                        'to' => StateObject::STATE_SUMMARY,
                        'metadata' => $finishMetadata,
                    ],
                    'change body details' => [
                        'from' => StateObject::STATE_SUMMARY,
                        'to' => StateObject::STATE_CHANGE_VEHICLE_BODY,
                    ],
                    'change body details save' => [
                        'name' => 'finish',
                        'from' => StateObject::STATE_CHANGE_VEHICLE_BODY,
                        'to' => StateObject::STATE_SUMMARY,
                        'metadata' => $finishMetadata,
                    ],
                    'change weight details' => [
                        'from' => StateObject::STATE_SUMMARY,
                        'to' => StateObject::STATE_CHANGE_VEHICLE_WEIGHT,
                    ],
                    'change weight details save' => [
                        'name' => 'finish',
                        'from' => StateObject::STATE_CHANGE_VEHICLE_WEIGHT,
                        'to' => StateObject::STATE_SUMMARY,
                        'metadata' => $finishMetadata,
                    ],
                ],
            ],
        ],
    ]);
};
