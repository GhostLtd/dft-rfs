<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\DomesticSurvey\IndexController;
use App\Workflow\DomesticSurvey\ClosingDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_closing_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_START,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_START,
                    StateObject::STATE_MISSING_DAYS,
                    StateObject::STATE_REASON_EMPTY_SURVEY,
                    StateObject::STATE_VEHICLE_FUEL,

                    StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                    StateObject::STATE_DRIVER_AVAILABILITY_WAGES,
                    StateObject::STATE_DRIVER_AVAILABILITY_BONUSES,
                    StateObject::STATE_DRIVER_AVAILABILITY_DELIVERIES,

                    StateObject::STATE_CONFIRM,
                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'missing_days' => [
                        'from' =>  StateObject::STATE_START,
                        'to' => StateObject::STATE_MISSING_DAYS,
                        'guard' => 'subject.getSubject().getDays().count() !== 7 && subject.getSubject().getIsInPossessionOfVehicle() === "yes"',
                    ],
                    'back_to_dashboard' => [
                        'from' => StateObject::STATE_MISSING_DAYS,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'redirectRoute' => IndexController::SUMMARY_ROUTE,
                            'transitionWhenFormData' => ['property' => 'missing_days', 'value' => false]
                        ],
                    ],
                    'request_fuel_added_after_missing_days' => [
                        'from' =>  StateObject::STATE_MISSING_DAYS,
                        'to' => StateObject::STATE_VEHICLE_FUEL,
                        'metadata' => [
                            'transitionWhenFormData' => ['property' => 'missing_days', 'value' => true]
                        ],
                        'guard' => 'subject.getSubject().hasJourneys()',
                    ],
                    'empty_survey' => [
                        'from' => StateObject::STATE_MISSING_DAYS,
                        'to' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'metadata' => [
                            'transitionWhenFormData' => ['property' => 'missing_days', 'value' => true]
                        ],
                        'guard' => '!subject.getSubject().hasJourneys()',
                    ],
                    'request_fuel_added_no_issues' => [
                        'from' =>  StateObject::STATE_START,
                        'to' => StateObject::STATE_VEHICLE_FUEL,
                        'guard' => 'subject.getSubject().hasJourneys() && subject.getSubject().getDays().count() === 7'
                    ],


                    // Driver availability
                    'driver_availability_not_in_possession' => [
                        'from' =>  StateObject::STATE_START,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'guard' => 'subject.getSubject().getIsInPossessionOfVehicle() !== "yes"',
                    ],

                    'driver_availability' => [
                        'from' => [StateObject::STATE_REASON_EMPTY_SURVEY, StateObject::STATE_VEHICLE_FUEL],
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'metadata' => [
                            'persist' => true,
                            'submitLabel' => 'Save and continue',
                        ],
                    ],
                    'driver_availability_has_vacancies' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_DELIVERIES,
                        'guard' => 'subject.getSubject().getSurvey().getDriverAvailability().getHasVacancies() === "yes"'
                    ],
                    'driver_availability_has_vacancies_done' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_DELIVERIES,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_WAGES,
                    ],
                    'driver_availability_no_vacancies' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_WAGES,
                        'guard' => 'subject.getSubject().getSurvey().getDriverAvailability().getHasVacancies() !== "yes"'
                    ],
                    'driver_availability_wages_done' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_WAGES,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_BONUSES,
                    ],
                    // [End] Driver availability


                    'request_confirmation' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_BONUSES,
                        'to' => StateObject::STATE_CONFIRM,
                        'metadata' => [
                            'persist' => true,
                            'submitLabel' => 'Save and continue',
                        ],
                    ],

                    'finish' => [
                        'from' => StateObject::STATE_CONFIRM,
                        'to' =>  StateObject::STATE_END,
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => IndexController::COMPLETED_ROUTE,
                            'submitLabel' => 'Confirm and submit survey',
                        ],
                    ],

                ]
            ],
        ],
    ]);
};