<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalPreEnquiry\PreEnquiryController;
use App\Workflow\InternationalPreEnquiry\PreEnquiryState as StateObject;

return static function (ContainerConfigurator $container) {
    $preEnquirySummaryRoute = PreEnquiryController::SUMMARY_ROUTE;

    $container->extension('framework', [
        'workflows' => [
            'pre_enquiry' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_COMPANY_NAME,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_INTRODUCTION,
                    StateObject::STATE_COMPANY_NAME,
                    StateObject::STATE_CORRESPONDENCE_DETAILS,
                    StateObject::STATE_CORRESPONDENCE_ADDRESS,
                    StateObject::STATE_VEHICLE_QUESTIONS,
                    StateObject::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                    StateObject::STATE_SUMMARY,

                    StateObject::STATE_CHANGE_COMPANY_NAME,
                    StateObject::STATE_CHANGE_CORRESPONDENCE_DETAILS,
                    StateObject::STATE_CHANGE_CORRESPONDENCE_ADDRESS,
                    StateObject::STATE_CHANGE_VEHICLE_QUESTIONS,
                    StateObject::STATE_CHANGE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                ],
                'transitions' => [
                    'start' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_COMPANY_NAME,
                    ],
                    'name-to-details' => [
                        'from' => StateObject::STATE_COMPANY_NAME,
                        'to' =>  StateObject::STATE_CORRESPONDENCE_DETAILS,
                    ],
                    'details-to-address' => [
                        'from' => StateObject::STATE_CORRESPONDENCE_DETAILS,
                        'to' =>  StateObject::STATE_CORRESPONDENCE_ADDRESS,
                    ],
                    'address-to-vehicles' => [
                        'from' => StateObject::STATE_CORRESPONDENCE_ADDRESS,
                        'to' =>  StateObject::STATE_VEHICLE_QUESTIONS,
                    ],
                    'vehicles-to-employees' => [
                        'from' => StateObject::STATE_VEHICLE_QUESTIONS,
                        'to' =>  StateObject::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => $preEnquirySummaryRoute,
                        ],
                        'from' => StateObject::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],

                    'name-change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_COMPANY_NAME,
                    ],
                    'name-changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => $preEnquirySummaryRoute,
                        ],
                        'from' =>  StateObject::STATE_CHANGE_COMPANY_NAME,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],

                    'address-change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_CORRESPONDENCE_ADDRESS,
                    ],
                    'address-changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => $preEnquirySummaryRoute,
                        ],
                        'from' =>  StateObject::STATE_CHANGE_CORRESPONDENCE_ADDRESS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],

                    'details-change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_CORRESPONDENCE_DETAILS,
                    ],
                    'details-changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => $preEnquirySummaryRoute,
                        ],
                        'from' =>  StateObject::STATE_CHANGE_CORRESPONDENCE_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],

                    'vehicle-change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_VEHICLE_QUESTIONS,
                    ],
                    'vehicle-changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => $preEnquirySummaryRoute,
                        ],
                        'from' =>  StateObject::STATE_CHANGE_VEHICLE_QUESTIONS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],

                    'employees-change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                    ],
                    'employees-changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => $preEnquirySummaryRoute,
                        ],
                        'from' =>  StateObject::STATE_CHANGE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                ]
            ],
        ],
    ]);
};