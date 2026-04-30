<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\RoRo\CommentsType;
use App\Form\RoRo\DataEntryType;
use App\Form\RoRo\IntroductionType;
use App\Form\RoRo\VehicleCountsType;
use App\Workflow\RoRo\RoRoState as StateObject;

return static function (ContainerConfigurator $container) {
    $endMetadata = [
        'persist' => true,
        'redirect_route' => [
            'name' => 'app_roro_survey_view',
            'parameters' => [
                'surveyId' => 'subject.id',
            ]
        ],
    ];

    $container->extension('framework', [
        'workflows' => [
            'roro' => [
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
                            'template' => 'roro/survey/form-introduction.html.twig',
                            'form_class' => IntroductionType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_DATA_ENTRY,
                        'metadata' => [
                            'template' => 'roro/survey/form-data-entry.html.twig',
                            'form_class' => DataEntryType::class,
                            'is_valid_alternative_start_place' => "state.getSubject().getState() !== 'new' and state.getSubject().getIsActiveForPeriod()",
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_VEHICLE_COUNTS,
                        'metadata' => [
                            'template' => 'roro/survey/form-vehicle-counts.html.twig',
                            'form_class' => VehicleCountsType::class,
                            'is_valid_alternative_start_place' => "state.getSubject().getState() !== 'new' and state.getSubject().getIsActiveForPeriod()",
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_COMMENTS,
                        'metadata' => [
                            'template' => 'roro/survey/form-comments.html.twig',
                            'form_class' => CommentsType::class,
                            'is_valid_alternative_start_place' => "state.getSubject().getState() !== 'new'",
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_FINISH,
                    ],
                ],
                'transitions' => [
                    // Introduction screen (is survey active?)
                    // -> Not active (wizard flow)
                    'not-active' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_COMMENTS,
                        'metadata' => [
                            'submit_label' => 'common.actions.continue'
                        ],
                        'guard' => '!subject.getSubject().getIsActiveForPeriod() && !subject.hasWizardPreviouslyCompleted()',
                    ],

                    // -> Not active (editing)
                    'not-active-editing' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => array_merge($endMetadata, [
                            // If the user chooses yes, the wizard continues, so prefer "continue" over "save and continue"
                            'submit_label' => 'common.actions.continue'
                        ]),
                        'guard' => '!subject.getSubject().getIsActiveForPeriod() && subject.hasWizardPreviouslyCompleted()',
                    ],

                    // -> Active
                    'active' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_VEHICLE_COUNTS,
                        'guard' => "subject.getSubject().getIsActiveForPeriod() and subject.getSubject().getDataEntryMethod() === 'manual'",
                    ],

                    // Data entry
                    'data-entry' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_DATA_ENTRY,
                        'guard' => "subject.getSubject().getIsActiveForPeriod() and subject.getSubject().getDataEntryMethod() === 'advanced'",
                    ],
                    'data-entry-to-vehicle-counts' => [
                        'from' => StateObject::STATE_DATA_ENTRY,
                        'to' =>  StateObject::STATE_VEHICLE_COUNTS,
                    ],

                    // Vehicle counts
                    'vehicle-counts-to-comments' => [
                        'from' => StateObject::STATE_VEHICLE_COUNTS,
                        'to' =>  StateObject::STATE_COMMENTS,
                        'guard' => '!subject.hasWizardPreviouslyCompleted()',
                    ],
                    'vehicle-counts-to-finish' => [
                        'from' => StateObject::STATE_VEHICLE_COUNTS,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => $endMetadata,
                        'guard' => 'subject.hasWizardPreviouslyCompleted()',
                    ],

                    // Comments
                    'comments-to-finish' => [
                        'from' => StateObject::STATE_COMMENTS,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => $endMetadata,
                    ],
                ]
            ],
        ],
    ]);
};