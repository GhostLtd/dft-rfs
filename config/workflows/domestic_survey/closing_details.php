<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\DomesticSurvey\IndexController;
use App\Form\DomesticSurvey\ClosingDetails\EarlyResponseType;
use App\Form\DomesticSurvey\ClosingDetails\MissingDaysType;
use App\Form\DomesticSurvey\ClosingDetails\ReasonEmptySurveyType;
use App\Form\DomesticSurvey\ClosingDetails\VehicleFuelType;
use App\Form\DomesticSurvey\DriverAvailability\BonusesType;
use App\Form\DomesticSurvey\DriverAvailability\DeliveriesType;
use App\Form\DomesticSurvey\DriverAvailability\DriversAndVacanciesType;
use App\Form\DomesticSurvey\DriverAvailability\WagesType;
use App\Workflow\DomesticSurvey\ClosingDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_closing_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_START,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_START,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/start.html.twig',
                            'is_valid_alternative_start_place' => 'state.getSubject().getIsInPossessionOfVehicle() !== "yes"',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_EARLY_RESPONSE,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/form-early-response.html.twig',
                            'form_class' => EarlyResponseType::class,
                            'form_data_property' => null,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_MISSING_DAYS,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/form-missing-days.html.twig',
                            'form_class' => MissingDaysType::class,
                            'form_data_property' => null,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/form-reason-for-empty-survey.html.twig',
                            'form_class' => ReasonEmptySurveyType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_VEHICLE_FUEL,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/form-vehicle-fuel.html.twig',
                            'form_class' => VehicleFuelType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/form-driver-availability-drivers.html.twig',
                            'form_class' => DriversAndVacanciesType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DRIVER_AVAILABILITY_WAGES,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/form-driver-availability-wages.html.twig',
                            'form_class' => WagesType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DRIVER_AVAILABILITY_BONUSES,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/form-driver-availability-bonuses.html.twig',
                            'form_class' => BonusesType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DRIVER_AVAILABILITY_DELIVERIES,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/form-driver-availability-deliveries.html.twig',
                            'form_class' => DeliveriesType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CONFIRM,
                        'metadata' => [
                            'template' => 'domestic_survey/closing_details/confirm.html.twig',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_END,
                    ],
                ],
                'transitions' => [
                    // Is early
                    'is_early' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_EARLY_RESPONSE,
                        'guard' => 'subject.getSubject().isInPossessionButIsEarlierThanSurveyPeriodEnd()',
                    ],
                    'is_early_to_dashboard' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_END,
                        'guard' => 'subject.getIsCorrect() === false',
                        'metadata' => [
                            'redirect_route' => IndexController::SUMMARY_ROUTE,
                        ],
                    ],
                    'is_early_to_missing_days' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_MISSING_DAYS,
                        'guard' => 'subject.getIsCorrect() === true && subject.getSubject().isInPossessionButHasNotCompletedAllDays()',
                    ],
                    'is_early_to_request_fuel' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_VEHICLE_FUEL,
                        'guard' => 'subject.getIsCorrect() === true && !subject.getSubject().isInPossessionButHasNotCompletedAllDays() && subject.getSubject().hasJourneys()',
                    ],
                    'is_early_to_empty_survey' => [
                        'from' => StateObject::STATE_EARLY_RESPONSE,
                        'to' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'guard' => 'subject.getIsCorrect() === true && !subject.getSubject().isInPossessionButHasNotCompletedAllDays() && !subject.getSubject().hasJourneys()',
                    ],

                    // Missing days
                    'missing_days' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_MISSING_DAYS,
                        'guard' => '!subject.getSubject().isInPossessionButIsEarlierThanSurveyPeriodEnd() && subject.getSubject().isInPossessionButHasNotCompletedAllDays()',
                    ],
                    'back_to_dashboard' => [
                        'from' => StateObject::STATE_MISSING_DAYS,
                        'to' => StateObject::STATE_END,
                        'guard' => 'subject.getMissingDays() === false',
                        'metadata' => [
                            'redirect_route' => IndexController::SUMMARY_ROUTE,
                        ],
                    ],
                    'request_fuel_added_after_missing_days' => [
                        'from' => StateObject::STATE_MISSING_DAYS,
                        'to' => StateObject::STATE_VEHICLE_FUEL,
                        'guard' => 'subject.getMissingDays() === true && subject.getSubject().hasJourneys()',
                    ],
                    'empty_survey' => [
                        'from' => StateObject::STATE_MISSING_DAYS,
                        'to' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'guard' => 'subject.getMissingDays() === true && !subject.getSubject().hasJourneys()',
                    ],

                    // Fuel request
                    'request_fuel_added_no_issues' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_VEHICLE_FUEL,
                        'guard' => '!subject.getSubject().isInPossessionButIsEarlierThanSurveyPeriodEnd() && subject.getSubject().isInPossessionAndHasCompletedAllDays() && subject.getSubject().hasJourneys()',
                    ],

                    // Driver availability
                    'driver_availability_not_in_possession' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'guard' => 'subject.getSubject().getIsInPossessionOfVehicle() !== "yes"',
                    ],
                    'driver_availability' => [
                        'from' => [StateObject::STATE_REASON_EMPTY_SURVEY, StateObject::STATE_VEHICLE_FUEL],
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'metadata' => [
                            'persist' => true,
                            'submit_label' => 'Save and continue',
                        ],
                    ],
                    'driver_availability_has_vacancies' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_DELIVERIES,
                        'guard' => 'subject.getSubject().getSurvey().getDriverAvailability().getHasVacancies() === "yes"',
                    ],
                    'driver_availability_has_vacancies_done' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_DELIVERIES,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_WAGES,
                    ],
                    'driver_availability_no_vacancies' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_DRIVERS,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_WAGES,
                        'guard' => 'subject.getSubject().getSurvey().getDriverAvailability().getHasVacancies() !== "yes"',
                    ],
                    'driver_availability_wages_done' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_WAGES,
                        'to' => StateObject::STATE_DRIVER_AVAILABILITY_BONUSES,
                    ],

                    'request_confirmation' => [
                        'from' => StateObject::STATE_DRIVER_AVAILABILITY_BONUSES,
                        'to' => StateObject::STATE_CONFIRM,
                        'metadata' => [
                            'persist' => true,
                            'submit_label' => 'Save and continue',
                        ],
                    ],

                    'finish' => [
                        'from' => StateObject::STATE_CONFIRM,
                        'to' => StateObject::STATE_END,
                        'metadata' => [
                            'persist' => true,
                            'redirect_route' => IndexController::COMPLETED_ROUTE,
                            'submit_label' => 'Confirm and submit survey',
                        ],
                    ],
                ],
            ],
        ],
    ]);
};
