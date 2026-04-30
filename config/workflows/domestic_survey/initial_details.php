<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\InitialDetails\ContactDetailsType;
use App\Form\DomesticSurvey\InitialDetails\HireeDetailsType;
use App\Form\DomesticSurvey\InitialDetails\InPossessionOfVehicleType;
use App\Form\DomesticSurvey\InitialDetails\ScrappedDetailsType;
use App\Form\DomesticSurvey\InitialDetails\SoldDetailsType;
use App\Workflow\DomesticSurvey\InitialDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_initial_details' => [
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
                            'template' => 'domestic_survey/initial_details/introduction.html.twig',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_REQUEST_CONTACT_DETAILS,
                        'metadata' => [
                            'template' => 'domestic_survey/initial_details/form-contact-details.html.twig',
                            'form_class' => ContactDetailsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_CHANGE_CONTACT_DETAILS,
                        'metadata' => [
                            'template' => 'domestic_survey/initial_details/form-contact-details.html.twig',
                            'form_class' => ContactDetailsType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_ASK_IN_POSSESSION,
                        'metadata' => [
                            'template' => 'domestic_survey/initial_details/form-in-possession.html.twig',
                            'form_class' => InPossessionOfVehicleType::class,
                            'is_valid_alternative_start_place' => '!is_empty(state.getSubject().getId())',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_ASK_HIREE_DETAILS,
                        'metadata' => [
                            'template' => 'domestic_survey/initial_details/form-hiree-details.html.twig',
                            'form_class' => HireeDetailsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_ASK_SCRAPPED_DETAILS,
                        'metadata' => [
                            'template' => 'domestic_survey/initial_details/form-scrapped-details.html.twig',
                            'form_class' => ScrappedDetailsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_ASK_SOLD_DETAILS,
                        'metadata' => [
                            'template' => 'domestic_survey/initial_details/form-sold-details.html.twig',
                            'form_class' => SoldDetailsType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_END,
                    ],
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
                            'redirect_route' => 'app_domesticsurvey_contactdetails',
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
                            'redirect_route' => 'app_domesticsurvey_contactdetails',
                            'submit_label' => 'Continue'
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
                            'redirect_route' => 'app_domesticsurvey_contactdetails'
                        ],
                        'from' =>  StateObject::STATE_CHANGE_CONTACT_DETAILS,
                        'to' =>  StateObject::STATE_END,
                    ],
                ]
            ],
        ],
    ]);
};