<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\BusinessAndCorrespondenceDetailsController;
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

                    StateObject::STATE_CHANGE_CONTACT_DETAILS,

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
                        'from' => StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                        'to' =>  StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'guard' => 'subject.getSubject().getAnnualInternationalJourneyCount() === 0'
                    ],
                    'positive trips entered' => [
                        'from' => StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                        'to' =>  StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'guard' => 'subject.getSubject().getAnnualInternationalJourneyCount() > 0'
                    ],
                    'still active' => [
                        'from' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'to' =>  StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'guard' => '! subject.getSubject().isNoLongerActive()'
                    ],
                    'finish via business details' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE,
                        ],
                        'from' => StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'finish via inactive business' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE,
                            'submitLabel' => 'Continue',
                        ],
                        'from' => StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'to' =>  StateObject::STATE_SUMMARY,
                        'guard' => 'subject.getSubject().isNoLongerActive()'
                    ],
                    'contact details change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_CHANGE_CONTACT_DETAILS,
                    ],
                    'contact details changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE,
                        ],
                        'from' =>  StateObject::STATE_CHANGE_CONTACT_DETAILS,
                        'to' =>  StateObject::STATE_SUMMARY,
                    ],
                    'number of trips change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_REQUEST_NUMBER_OF_TRIPS,
                    ],
                    'business details change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_REQUEST_BUSINESS_DETAILS,
                        'guard' => '! subject.getSubject().isNoLongerActive()'
                    ],
                    'activity status change' => [
                        'from' =>  StateObject::STATE_SUMMARY,
                        'to' =>  StateObject::STATE_REQUEST_ACTIVITY_STATUS,
                        'guard' => 'subject.getSubject().isNoLongerActive()'
                    ],
                ]
            ],
        ],
    ]);
};