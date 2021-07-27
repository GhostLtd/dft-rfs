<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\PreEnquiry\PreEnquiryController;
use App\Workflow\PreEnquiry\PreEnquiryState as StateObject;

return static function (ContainerConfigurator $container) {
    $preEnquirySummaryRoute = PreEnquiryController::SUMMARY_ROUTE;

    $endMetadata = [
        'persist' => true,
        'redirectRoute' => $preEnquirySummaryRoute,
    ];

    $guardUncommitted = 'is_empty(subject.getSubject().getId())';
    $guardCommitted = "!{$guardUncommitted}";

    $container->extension('framework', [
        'workflows' => [
            'pre_enquiry_initial_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_INTRODUCTION,
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
                ],
                'transitions' => [
                    'start' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_COMPANY_NAME,
                        'guard' => $guardUncommitted,
                    ],
                    'name-to-details' => [
                        'from' => StateObject::STATE_COMPANY_NAME,
                        'to' =>  StateObject::STATE_CORRESPONDENCE_DETAILS,
                        'guard' => $guardUncommitted,
                    ],
                    'details-to-address' => [
                        'from' => StateObject::STATE_CORRESPONDENCE_DETAILS,
                        'to' =>  StateObject::STATE_CORRESPONDENCE_ADDRESS,
                        'guard' => $guardUncommitted,
                    ],
                    'address-to-vehicles' => [
                        'from' => StateObject::STATE_CORRESPONDENCE_ADDRESS,
                        'to' =>  StateObject::STATE_VEHICLE_QUESTIONS,
                        'guard' => $guardUncommitted,
                    ],
                    'vehicles-to-employees' => [
                        'from' => StateObject::STATE_VEHICLE_QUESTIONS,
                        'to' =>  StateObject::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                        'guard' => $guardUncommitted,
                    ],
                    'finish' => [
                        'metadata' => $endMetadata,
                        'from' => StateObject::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardUncommitted,
                    ],

                    // -----

                    'name-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_COMPANY_NAME,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardCommitted,
                    ],
                    'address-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_CORRESPONDENCE_ADDRESS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardCommitted,
                    ],
                    'details-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_CORRESPONDENCE_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardCommitted,
                    ],
                    'vehicle-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_VEHICLE_QUESTIONS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardCommitted,
                    ],
                    'employees-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardCommitted,
                    ],
                ]
            ],
        ],
    ]);
};