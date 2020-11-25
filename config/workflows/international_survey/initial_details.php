<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\IndexController;
use App\Workflow\InternationalSurvey\InitialDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_initial_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_INTRODUCTION,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_INTRODUCTION,
                    StateObject::STATE_REQUEST_CONTACT_DETAILS,
                    StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                    StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                    StateObject::STATE_REQUEST_BUSINESS_DETAILS,

                    StateObject::STATE_SUMMARY,
                ],
                'transitions' => [
                    'start' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_REQUEST_CONTACT_DETAILS,
                    ],
                    'contact details entered' => [
                        'from' => StateObject::STATE_REQUEST_CONTACT_DETAILS,
                        'to' =>  StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                    ],
                    'zero trips entered' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'annualInternationalJourneyCount', 'value' => 0]],
                        'from' => StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                        'to' =>  StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                    ],
                    'positive trips entered' => [
                        'metadata' => ['transitionWhenCallback' => 'hasPositiveTrips'],
                        'from' => StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                        'to' =>  StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                    ],
                    'still active' => [
                        'metadata' => ['transitionWhenCallbackNot' => 'isNoLongerActive'],
                        'from' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'to' =>  StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                    ],
                    'finish via business details' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => IndexController::SUMMARY_ROUTE,
                        ],
                        'from' => StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'finish via inactive business' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => IndexController::SUMMARY_ROUTE,
                            'transitionWhenCallback' => 'isNoLongerActive',
                        ],
                        'from' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                ]
            ],
        ],
    ]);
};