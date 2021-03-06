<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\Domestic\SurveyResponse;
use App\Workflow\DomesticSurvey\InitialDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_initial_details' => [
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
                    StateObject::STATE_CHANGE_CONTACT_DETAILS,
                    StateObject::STATE_ASK_IN_POSSESSION,
                    StateObject::STATE_ASK_HIREE_DETAILS,
                    StateObject::STATE_ASK_SCRAPPED_DETAILS,
                    StateObject::STATE_ASK_SOLD_DETAILS,
                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'start' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_REQUEST_CONTACT_DETAILS,
                    ],
                    'contact-details-to-in-possession' => [
                        'from' => StateObject::STATE_REQUEST_CONTACT_DETAILS,
                        'to' =>  StateObject::STATE_ASK_IN_POSSESSION,
                    ],
                    'request-hiree-details' => [
                        'from' => StateObject::STATE_ASK_IN_POSSESSION,
                        'to' =>  StateObject::STATE_ASK_HIREE_DETAILS,
                        'guard' => sprintf("subject.getSubject().getIsInPossessionOfVehicle() === '%s'", SurveyResponse::IN_POSSESSION_ON_HIRE),
                    ],
                    'request-scrapped-details' => [
                        'from' => StateObject::STATE_ASK_IN_POSSESSION,
                        'to' =>  StateObject::STATE_ASK_SCRAPPED_DETAILS,
                        'guard' => sprintf("subject.getSubject().getIsInPossessionOfVehicle() === '%s'", SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN),
                    ],
                    'request-sold-details' => [
                        'from' => StateObject::STATE_ASK_IN_POSSESSION,
                        'to' =>  StateObject::STATE_ASK_SOLD_DETAILS,
                        'guard' => sprintf("subject.getSubject().getIsInPossessionOfVehicle() === '%s'", SurveyResponse::IN_POSSESSION_SOLD),
                    ],
                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            // The contact details route redirects to the index if business details is incomplete
                            // Redirecting here, means we always end up in the right place regardless of state
                            'redirectRoute' => 'app_domesticsurvey_contactdetails',
                        ],
                        'from' => [
                            StateObject::STATE_ASK_HIREE_DETAILS,
                            StateObject::STATE_ASK_SCRAPPED_DETAILS,
                            StateObject::STATE_ASK_SOLD_DETAILS,
                        ],
                        'to' =>  StateObject::STATE_END,
                    ],
                    'finish_2' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'app_domesticsurvey_contactdetails',
                            'submitLabel' => 'Continue'
                        ],
                        'from' => StateObject::STATE_ASK_IN_POSSESSION,
                        'to' =>  StateObject::STATE_END,
                        'guard' => sprintf("subject.getSubject().getIsInPossessionOfVehicle() === '%s'", SurveyResponse::IN_POSSESSION_YES),
                    ],
                    'change-contact-details' => [
                        'from' =>  StateObject::STATE_END,
                        'to' =>  StateObject::STATE_CHANGE_CONTACT_DETAILS,
                    ],
                    'change-in-possession' => [
                        'from' =>  StateObject::STATE_END,
                        'to' =>  StateObject::STATE_ASK_IN_POSSESSION,
                    ],
                    'contact details changed' => [
                        'name' => 'finish',
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'app_domesticsurvey_contactdetails'
                        ],
                        'from' =>  StateObject::STATE_CHANGE_CONTACT_DETAILS,
                        'to' =>  StateObject::STATE_END,
                    ],
                ]
            ],
        ],
    ]);
};