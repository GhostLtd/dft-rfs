<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Entity\DomesticSurveyResponse;
use App\Workflow\DomesticSurvey\DayMultipleState as StateObject;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'workflows' => [
            'domestic_survey_day_multi' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_DEPARTED,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_DEPARTED,
                    StateObject::STATE_DEPARTED_MODE_CHANGE,
                    StateObject::STATE_ARRIVED,
                    StateObject::STATE_ARRIVED_MODE_CHANGE,
                    StateObject::STATE_NEXT,
//                    StateObject::STATE_,

                    StateObject::STATE_END,
                ],
                'transitions' => [
                    'departed-to-departed-mode-change' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsLoaded', 'value' => true]],
                        'from' => StateObject::STATE_DEPARTED,
                        'to' =>  StateObject::STATE_DEPARTED_MODE_CHANGE,
                    ],
                    'departed-mode-change-to-arrived' => [
                        'from' => StateObject::STATE_DEPARTED_MODE_CHANGE,
                        'to' =>  StateObject::STATE_ARRIVED,
                    ],
                    'departed-to-arrived' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsLoaded', 'value' => false]],
                        'from' => StateObject::STATE_DEPARTED,
                        'to' =>  StateObject::STATE_ARRIVED,
                    ],
                    'arrived-to-arrived-mode-change' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsUnloaded', 'value' => true]],
                        'from' => StateObject::STATE_ARRIVED,
                        'to' =>  StateObject::STATE_ARRIVED_MODE_CHANGE,
                    ],
                    'arrived-mode-change-to-next' => [
                        'from' => StateObject::STATE_ARRIVED_MODE_CHANGE,
                        'to' =>  StateObject::STATE_NEXT,
                    ],
                    'arrived-to-next' => [
                        'metadata' => ['transitionWhenFormData' => ['property' => 'goodsUnloaded', 'value' => false]],
                        'from' => StateObject::STATE_ARRIVED,
                        'to' =>  StateObject::STATE_NEXT,
                    ],



                    'finish' => [
                        'metadata' => [
                            'persist' => true,
                            'redirectRoute' => 'domestic_survey_index',
                        ],
                        'from' => StateObject::STATE_NEXT,
                        'to' =>  StateObject::STATE_END,
                    ],
                ]
            ],
        ],
    ]);
};