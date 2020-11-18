<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\InternationalPreEnquiry\PreEnquiryState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_pre_survey' => [
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
                    StateObject::STATE_END,
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
                            'redirectRoute' => 'app_internationalpreenquiry_start',
                        ],
                        'from' => StateObject::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS,
                        'to' =>  StateObject::STATE_END,
                    ],
                ]
            ],
        ],
    ]);
};