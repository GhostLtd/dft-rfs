<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Form\Admin\RoRo\OperatorGroupType;
use App\Workflow\RoRo\OperatorGroupState as StateObject;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;

return static function (ContainerConfigurator $container) {
    $endMetadata = [
        'persist' => true,
        'redirect_route' => ['name' => 'admin_operator_groups_list'],
    ];

    $container->extension('framework', [
        'workflows' => [
            'operator_group' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_CHOOSE_NAME,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'place',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    [
                        'name' => StateObject::STATE_CHOOSE_NAME,
                        'metadata' => [
                            'template' => 'admin/roro/operator-groups/add/form-choose-name.html.twig',
                            'form_class' => OperatorGroupType::class,
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_PREVIEW,
                        'metadata' => [
                            'template' => 'admin/roro/operator-groups/add/form-preview.html.twig',
                        ],
                    ],
                    [
                        'name' => StateObject::STATE_FINISH,
                    ],
                ],
                'transitions' => [
                    'choose-name-to-preview' => [
                        'from' => StateObject::STATE_CHOOSE_NAME,
                        'to' =>  StateObject::STATE_PREVIEW,
                    ],
                    'preview-to-finish-add' => [
                        'from' => StateObject::STATE_PREVIEW,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => array_merge($endMetadata, [
                            'notification_banner' => [
                                'title' => 'admin.operator-groups.add.success-notification.title',
                                'heading' => 'admin.operator-groups.add.success-notification.heading',
                                'content' => 'admin.operator-groups.add.success-notification.content',
                                'options' => ['style' => NotificationBanner::STYLE_SUCCESS],
                                'translation_parameters' => [
                                    'name' => 'subject.name',
                                ],
                            ],
                        ]),
                        'guard' => "subject.getMode() === 'add'"
                    ],
                    'preview-to-finish-edit' => [
                        'from' => StateObject::STATE_PREVIEW,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => array_merge($endMetadata, [
                            'notification_banner' => [
                                'title' => 'admin.operator-groups.edit.success-notification.title',
                                'heading' => 'admin.operator-groups.edit.success-notification.heading',
                                'content' => 'admin.operator-groups.edit.success-notification.content',
                                'options' => ['style' => NotificationBanner::STYLE_SUCCESS],
                                'translation_parameters' => [
                                    'name' => 'subject.name',
                                ],
                            ],
                        ]),
                        'guard' => "subject.getMode() === 'edit'"
                    ],
                ]
            ],
        ],
    ]);
};