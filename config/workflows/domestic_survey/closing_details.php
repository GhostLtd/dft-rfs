<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\DomesticSurvey\ClosingDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_closing_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_VEHICLE_FUEL,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_VEHICLE_FUEL,
                    StateObject::STATE_REASON_EMPTY_SURVEY,
                    StateObject::STATE_CONFIRM,
                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'not_empty_survey' => [
                        'from' =>  StateObject::STATE_VEHICLE_FUEL,
                        'to' => StateObject::STATE_CONFIRM,
                        'metadata' => [
                            'persist' => true,
                            'buttonLabel' => 'Save and continue',
                        ],
                    ],
                    'empty_survey' => [
                        'from' => StateObject::STATE_VEHICLE_FUEL,
                        'to' => StateObject::STATE_REASON_EMPTY_SURVEY,
                    ],
                    'empty_survey_request_confirmation' => [
                        'from' =>  StateObject::STATE_REASON_EMPTY_SURVEY,
                        'to' => StateObject::STATE_CONFIRM,
                        'metadata' => [
                            'persist' => true,
                            'buttonLabel' => 'Save and continue',
                        ],
                    ],

                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'app_domesticsurvey_closed',
                            'buttonLabel' => 'Confirm and submit survey',
                        ],
                        'from' => StateObject::STATE_CONFIRM,
                        'to' =>  StateObject::STATE_END,
                    ],

                ]
            ],
        ],
    ]);
};