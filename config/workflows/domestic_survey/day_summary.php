<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\DomesticSurvey\DayMultipleState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_day_summary' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_END,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
//                    StateObject::STATE_,

                    StateObject::STATE_END,
                ],
                'transitions' => [


                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                        ],
                        'from' => StateObject::STATE_END,
                        'to' =>  StateObject::STATE_END,
                    ],
                ]
            ],
        ],
    ]);
};