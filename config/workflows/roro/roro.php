<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\RoRo\RoRoState as StateObject;

return static function (ContainerConfigurator $container) {
    $endMetadata = [
        'persist' => true,
        'redirectRoute' => [
            'routeName' => 'app_roro_survey_view',
            'parameterMappings' => [
                'surveyId' => 'id',
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
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_INTRODUCTION,
                    StateObject::STATE_DATA_ENTRY,
                    StateObject::STATE_VEHICLE_COUNTS,
                    StateObject::STATE_COMMENTS,
                    StateObject::STATE_FINISH,
                ],
                'transitions' => [
                    // Introduction screen (is survey active?)
                    // -> Not active (wizard flow)
                    'not-active' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_COMMENTS,
                        'metadata' => [
                            'submitLabel' => 'common.actions.continue'
                        ],
                        'guard' => '!subject.getSubject().getIsActiveForPeriod() && !subject.hasWizardPreviouslyCompleted()',
                    ],

                    // -> Not active (editing)
                    'not-active-editing' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => array_merge($endMetadata, [
                            // If the user chooses yes, the wizard continues, so prefer "continue" over "save and continue"
                            'submitLabel' => 'common.actions.continue'
                        ]),
                        'guard' => '!subject.getSubject().getIsActiveForPeriod() && subject.hasWizardPreviouslyCompleted()',
                    ],

                    // -> Active
                    'active' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_VEHICLE_COUNTS,
                        'metadata' => [
                            'transitionWhenFormData' => ['property' => 'dataEntryMethod', 'value' => 'manual']
                        ],
                        'guard' => 'subject.getSubject().getIsActiveForPeriod()',
                    ],

                    // Data entry
                    'data-entry' => [
                        'from' => StateObject::STATE_INTRODUCTION,
                        'to' =>  StateObject::STATE_DATA_ENTRY,
                        'metadata' => [
                            'transitionWhenFormData' => ['property' => 'dataEntryMethod', 'value' => 'advanced']
                        ],
                        'guard' => 'subject.getSubject().getIsActiveForPeriod()',
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