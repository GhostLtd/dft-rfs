<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Controller\InternationalSurvey\IndexController;
use App\Workflow\InternationalSurvey\ClosingDetailsState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'international_survey_closing_details' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_START,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_START,
                    StateObject::STATE_REASON_EMPTY_SURVEY,
                    StateObject::STATE_CONFIRM,
                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'not_filled_out' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_REASON_EMPTY_SURVEY,
                    ],
                    'filled_out' => [
                        'from' => StateObject::STATE_START,
                        'to' => StateObject::STATE_CONFIRM,
                    ],
                    'request_confirmation' => [
                        'from' => StateObject::STATE_REASON_EMPTY_SURVEY,
                        'to' => StateObject::STATE_CONFIRM,
                    ],
                    'finish' => [
                        'from' => StateObject::STATE_CONFIRM,
                        'to' =>  StateObject::STATE_END,
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => IndexController::SUMMARY_ROUTE,
                            'buttonLabel' => 'Confirm and submit survey',
                        ],
                    ],

                ]
            ],
        ],
    ]);
};