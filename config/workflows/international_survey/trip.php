<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\TripController;
use App\Form\InternationalSurvey\Trip\ChangedBodyTypeType;
use App\Form\InternationalSurvey\Trip\CountriesTransittedType;
use App\Form\InternationalSurvey\Trip\DatesType;
use App\Form\InternationalSurvey\Trip\DistanceType;
use App\Form\InternationalSurvey\Trip\OriginAndDestinationType;
use App\Form\InternationalSurvey\Trip\OutboundPortsAndCargoStateType;
use App\Form\InternationalSurvey\Trip\ReturnPortsAndCargoStateType;
use App\Form\InternationalSurvey\Trip\SwappedTrailerType;
use App\Form\InternationalSurvey\Trip\VehicleWeightType;
use App\Workflow\InternationalSurvey\TripState as StateObject;

return static function (ContainerConfigurator $container) {
    $commonEditConfig = [
        'to' => StateObject::STATE_SUMMARY,
        'name' => 'finish_editing',
        'guard' => '!is_empty(subject.getSubject().getId())',
        'metadata' => [
            'persist' => true,
            'redirect_route' => [
                'name' => TripController::TRIP_ROUTE,
                'parameters' => ['id' => 'subject.id'],
            ],
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'international_survey_trip' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_ORIGIN_AND_DESTINATION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_ORIGIN_AND_DESTINATION,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-origin-and-destination.html.twig',
                            'form_class' => OriginAndDestinationType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DATES,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-dates.html.twig',
                            'form_class' => DatesType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_OUTBOUND_PORTS,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-outbound-ports.html.twig',
                            'form_class' => OutboundPortsAndCargoStateType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_RETURN_PORTS,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-return-ports.html.twig',
                            'form_class' => ReturnPortsAndCargoStateType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_SWAPPED_TRAILER,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-swapped-trailer.html.twig',
                            'form_class' => SwappedTrailerType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGED_BODY_TYPE,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-changed-body-type.html.twig',
                            'form_class' => ChangedBodyTypeType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-vehicle-weights.html.twig',
                            'form_class' => VehicleWeightType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DISTANCE,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-distance.html.twig',
                            'form_class' => DistanceType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId()) and state.getSubject().isDistanceEnabled()',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_COUNTRIES_TRANSITTED,
                        'metadata' => [
                            'template' => 'international_survey/trip/form-countries-transitted.html.twig',
                            'form_class' => CountriesTransittedType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_SUMMARY,
                    ],
                ],
                'transitions' => [
                    'places entered' => [
                        'from' => StateObject::STATE_ORIGIN_AND_DESTINATION,
                        'to' => StateObject::STATE_DATES,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'dates entered' => [
                        'from' => StateObject::STATE_DATES,
                        'to' => StateObject::STATE_OUTBOUND_PORTS,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'outbound ports entered' => [
                        'from' => StateObject::STATE_OUTBOUND_PORTS,
                        'to' => StateObject::STATE_RETURN_PORTS,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'return ports entered' => [
                        'from' => StateObject::STATE_RETURN_PORTS,
                        'to' => StateObject::STATE_SWAPPED_TRAILER,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],

                    /** START: trip distance removal */
                    'didnt swap trailer entered cant change body type (including trip distance)' => [
                        'from' => StateObject::STATE_SWAPPED_TRAILER,
                        'to' => StateObject::STATE_DISTANCE,
                        'guard' => 'is_empty(subject.getSubject().getId())
                                  && !subject.getSubject().canChangeBodyType()
                                  && !subject.getSubject().getIsSwappedTrailer()
                                  && subject.getSubject().isDistanceEnabled()',
                    ],
                    'didnt swap trailer entered cant change body type' => [
                        'from' => StateObject::STATE_SWAPPED_TRAILER,
                        'to' => StateObject::STATE_COUNTRIES_TRANSITTED,
                        'guard' => 'is_empty(subject.getSubject().getId())
                                  && !subject.getSubject().canChangeBodyType()
                                  && !subject.getSubject().getIsSwappedTrailer()
                                  && !subject.getSubject().isDistanceEnabled()',
                    ],
                    /** END: trip distance removal */

                    'swapped trailer entered cant change body type' => [
                        'from' => StateObject::STATE_SWAPPED_TRAILER,
                        'to' => StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'guard' => '!subject.getSubject().canChangeBodyType()
                                  && subject.getSubject().getIsSwappedTrailer()',
                    ],
                    'swapped trailer entered can change body type' => [
                        'from' => StateObject::STATE_SWAPPED_TRAILER,
                        'to' => StateObject::STATE_CHANGED_BODY_TYPE,
                        'guard' => 'subject.getSubject().canChangeBodyType()',
                    ],

                    /** START: trip distance removal */
                    'can change body type but not changed trailer or body (including trip distance)' => [
                        'from' => StateObject::STATE_CHANGED_BODY_TYPE,
                        'to' => StateObject::STATE_DISTANCE,
                        'guard' => 'is_empty(subject.getSubject().getId())
                                  && !subject.getSubject().canChangeWeights()
                                  && subject.getSubject().isDistanceEnabled()',
                    ],
                    'can change body type but not changed trailer or body' => [
                        'from' => StateObject::STATE_CHANGED_BODY_TYPE,
                        'to' => StateObject::STATE_COUNTRIES_TRANSITTED,
                        'guard' => 'is_empty(subject.getSubject().getId())
                                  && !subject.getSubject().canChangeWeights()
                                  && !subject.getSubject().isDistanceEnabled()',
                    ],
                    /** END: trip distance removal */

                    'can change body and changed trailer or body' => [
                        'from' => StateObject::STATE_CHANGED_BODY_TYPE,
                        'to' => StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'guard' => 'subject.getSubject().canChangeWeights()',
                    ],

                    /** START: trip distance removal */
                    'new vehicle weights entered can change body type (including trip distance)' => [
                        'from' => StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'to' => StateObject::STATE_DISTANCE,
                        'guard' => 'is_empty(subject.getSubject().getId())
                                  && subject.getSubject().isDistanceEnabled()',
                    ],
                    'new vehicle weights entered can change body type' => [
                        'from' => StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'to' => StateObject::STATE_COUNTRIES_TRANSITTED,
                        'guard' => 'is_empty(subject.getSubject().getId())
                                  && !subject.getSubject().isDistanceEnabled()',
                    ],
                    /** END: trip distance removal */

                    'distance entered' => [
                        'from' => StateObject::STATE_DISTANCE,
                        'to' => StateObject::STATE_COUNTRIES_TRANSITTED,
                        'guard' => 'is_empty(subject.getSubject().getId())',
                    ],
                    'finish' => [
                        'from' => StateObject::STATE_COUNTRIES_TRANSITTED,
                        'to' => StateObject::STATE_SUMMARY,
                        'metadata' => [
                            'persist' => true,
                            'redirect_route' => [
                                'name' => TripController::TRIP_ROUTE,
                                'parameters' => ['id' => 'subject.id'],
                            ],
                        ],
                    ],

                    // Editing
                    'editing places' => array_merge($commonEditConfig, ['from' => StateObject::STATE_ORIGIN_AND_DESTINATION]),
                    'editing dates' => array_merge($commonEditConfig, ['from' => StateObject::STATE_DATES]),
                    'editing outbound ports' => array_merge($commonEditConfig, ['from' => StateObject::STATE_OUTBOUND_PORTS]),
                    'editing return ports' => array_merge($commonEditConfig, ['from' => StateObject::STATE_RETURN_PORTS]),
                    'editing trailer cant change body type' => array_merge($commonEditConfig, [
                        'from' => StateObject::STATE_SWAPPED_TRAILER,
                        'guard' => '!is_empty(subject.getSubject().getId())
                                  && !subject.getSubject().canChangeBodyType()
                                  && !subject.getSubject().getIsSwappedTrailer()',
                        'metadata' => array_merge($commonEditConfig['metadata'], [
                            'submit_label' => 'common.actions.continue',
                        ]),
                    ]),
                    'editing body type not changed' => array_merge($commonEditConfig, [
                        'from' => StateObject::STATE_CHANGED_BODY_TYPE,
                        'guard' => '!is_empty(subject.getSubject().getId())
                                  && !subject.getSubject().canChangeWeights()',
                    ]),
                    'editing vehicle weights changed' => array_merge($commonEditConfig, [
                        'from' => StateObject::STATE_NEW_VEHICLE_WEIGHTS,
                        'guard' => '!is_empty(subject.getSubject().getId())
                                  && subject.getSubject().canChangeWeights()',
                    ]),
                    'editing distance' => array_merge($commonEditConfig, ['from' => StateObject::STATE_DISTANCE]),
                ],
            ],
        ],
    ]);
};
