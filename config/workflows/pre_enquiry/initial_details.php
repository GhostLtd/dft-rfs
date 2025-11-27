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

    $guardNotPersisted = 'is_empty(subject.getSubject().getId())';
    $guardPersisted = "!{$guardNotPersisted}";

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
                    StateObject::STATE_HGV_DETAILS,
                    StateObject::STATE_LGV_DETAILS,
                    StateObject::STATE_BUSINESS_DETAILS,
                    StateObject::STATE_SUMMARY,
                ],
                'transitions' => [
                    'start' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_COMPANY_NAME,
                        'guard' => $guardNotPersisted,
                    ],
                    'name-to-details' => [
                        'from' => StateObject::STATE_COMPANY_NAME,
                        'to' =>  StateObject::STATE_CORRESPONDENCE_DETAILS,
                        'guard' => $guardNotPersisted,
                    ],
                    'details-to-address' => [
                        'from' => StateObject::STATE_CORRESPONDENCE_DETAILS,
                        'to' =>  StateObject::STATE_CORRESPONDENCE_ADDRESS,
                        'guard' => $guardNotPersisted,
                    ],
                    'address-to-hgv' => [
                        'from' => StateObject::STATE_CORRESPONDENCE_ADDRESS,
                        'to' =>  StateObject::STATE_HGV_DETAILS,
                        'guard' => $guardNotPersisted,
                    ],
                    'hgv-to-lgv' => [
                        'from' => StateObject::STATE_HGV_DETAILS,
                        'to' =>  StateObject::STATE_LGV_DETAILS,
                        'guard' => $guardNotPersisted,
                    ],
                    'lgv-to-employees' => [
                        'from' => StateObject::STATE_LGV_DETAILS,
                        'to' =>  StateObject::STATE_BUSINESS_DETAILS,
                        'guard' => $guardNotPersisted,
                    ],
                    'finish' => [
                        'metadata' => $endMetadata,
                        'from' => StateObject::STATE_BUSINESS_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardNotPersisted,
                    ],

                    // -----

                    'name-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_COMPANY_NAME,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardPersisted,
                    ],
                    'address-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_CORRESPONDENCE_ADDRESS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardPersisted,
                    ],
                    'details-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_CORRESPONDENCE_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardPersisted,
                    ],
                    'hgv-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_HGV_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardPersisted,
                    ],
                    'lgv-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_LGV_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardPersisted,
                    ],
                    'employees-changed' => [
                        'metadata' => $endMetadata,
                        'from' =>  StateObject::STATE_BUSINESS_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => $guardPersisted,
                    ],
                ]
            ],
        ],
    ]);
};