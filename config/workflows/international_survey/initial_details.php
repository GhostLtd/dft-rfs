<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\BusinessAndCorrespondenceDetailsController;
use App\Form\InternationalSurvey\InitialDetails\ActivityStatusType;
use App\Form\InternationalSurvey\InitialDetails\BusinessDetailsType;
use App\Form\InternationalSurvey\InitialDetails\ContactDetailsType;
use App\Form\InternationalSurvey\InitialDetails\NumberOfTripsType;
use App\Workflow\InternationalSurvey\InitialDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_initial_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_INTRODUCTION,
                        'metadata' => [
                            'template' => 'international_survey/initial_details/introduction.html.twig',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_CONTACT_DETAILS,
                        'metadata' => [
                            'template' => 'international_survey/initial_details/form-contact-details.html.twig',
                            'form_class' => ContactDetailsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                        'metadata' => [
                            'template' => 'international_survey/initial_details/form-number-of-trips.html.twig',
                            'form_class' => NumberOfTripsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId()) and !state.getSubject().isNoLongerActive()',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'metadata' => [
                            'template' => 'international_survey/initial_details/form-activity-status.html.twig',
                            'form_class' => ActivityStatusType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'metadata' => [
                            'template' => 'international_survey/initial_details/form-business-details.html.twig',
                            'form_class' => BusinessDetailsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId()) and !state.getSubject().isNoLongerActive()',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_CONTACT_DETAILS,
                        'metadata' => [
                            'template' => 'international_survey/initial_details/form-contact-details.html.twig',
                            'form_class' => ContactDetailsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_SUMMARY,
                    ],
                ],
                'transitions' => [
                    'start' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' => StateObject::STATE_REQUEST_CONTACT_DETAILS,
                    ],
                    'contact details entered' => [
                        'from' => StateObject::STATE_REQUEST_CONTACT_DETAILS,
                        'to' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                    ],
                    'still active' => [
                        'from' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'to' => StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'guard' => '!subject.getSubject().isNoLongerActive()',
                    ],
                    'business details entered' => [
                        'from' => StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'to' => StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                    ],
                    'finish via number of trips' => [
                        'name' => 'finish',
                        'from' => StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                        'to' => StateObject::STATE_SUMMARY,
                        'metadata' => [
                            'persist' => true,
                            'redirect_route' => BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE,
                        ],
                    ],
                    'finish via inactive business' => [
                        'name' => 'finish',
                        'from' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'to' => StateObject::STATE_SUMMARY,
                        'guard' => 'subject.getSubject().isNoLongerActive()',
                        'metadata' => [
                            'persist' => true,
                            'redirect_route' => BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE,
                            'submit_label' => 'Continue',
                        ],
                    ],
                    'contact details change' => [
                        'from' => StateObject::STATE_SUMMARY,
                        'to' => StateObject::STATE_CHANGE_CONTACT_DETAILS,
                    ],
                    'contact details changed' => [
                        'name' => 'finish',
                        'from' => StateObject::STATE_CHANGE_CONTACT_DETAILS,
                        'to' => StateObject::STATE_SUMMARY,
                        'metadata' => [
                            'persist' => true,
                            'redirect_route' => BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE,
                        ],
                    ],
                    'number of trips change' => [
                        'from' => StateObject::STATE_SUMMARY,
                        'to' => StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                        'guard' => '!subject.getSubject().isNoLongerActive()',
                    ],
                    'business details change' => [
                        'from' => StateObject::STATE_SUMMARY,
                        'to' => StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'guard' => '!subject.getSubject().isNoLongerActive()',
                    ],
                    'activity status change' => [
                        'from' => StateObject::STATE_SUMMARY,
                        'to' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'guard' => 'subject.getSubject().isNoLongerActive()',
                    ],
                ],
            ],
        ],
    ]);
};
