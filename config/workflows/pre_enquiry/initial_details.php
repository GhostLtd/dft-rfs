<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\PreEnquiry\PreEnquiryController;
use App\Form\PreEnquiry\BusinessDetailsType;
use App\Form\PreEnquiry\CompanyNameType;
use App\Form\PreEnquiry\CorrespondenceAddressType;
use App\Form\PreEnquiry\CorrespondenceDetailsType;
use App\Form\PreEnquiry\HgvDetailsType;
use App\Form\PreEnquiry\LgvDetailsType;
use App\Workflow\PreEnquiry\PreEnquiryState as StateObject;

return static function (ContainerConfigurator $container) {
    $preEnquirySummaryRoute = PreEnquiryController::SUMMARY_ROUTE;

    $endMetadata = [
        'persist' => true,
        'redirect_route' => $preEnquirySummaryRoute,
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
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_INTRODUCTION,
                        'metadata' => [
                            'template' => 'pre_enquiry/introduction.html.twig',
                            'is_valid_start_place' => 'is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_COMPANY_NAME,
                        'metadata' => [
                            'template' => 'pre_enquiry/form-company-name.html.twig',
                            'form_class' => CompanyNameType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CORRESPONDENCE_DETAILS,
                        'metadata' => [
                            'template' => 'pre_enquiry/form-correspondence-details.html.twig',
                            'form_class' => CorrespondenceDetailsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CORRESPONDENCE_ADDRESS,
                        'metadata' => [
                            'template' => 'pre_enquiry/form-correspondence-address.html.twig',
                            'form_class' => CorrespondenceAddressType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_HGV_DETAILS,
                        'metadata' => [
                            'template' => 'pre_enquiry/form-hgv-details.html.twig',
                            'form_class' => HgvDetailsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_LGV_DETAILS,
                        'metadata' => [
                            'template' => 'pre_enquiry/form-lgv-details.html.twig',
                            'form_class' => LgvDetailsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_BUSINESS_DETAILS,
                        'metadata' => [
                            'template' => 'pre_enquiry/form-business-details.html.twig',
                            'form_class' => BusinessDetailsType::class,
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