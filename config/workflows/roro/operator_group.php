<?php
namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Workflow\RoRo\OperatorGroupState as StateObject;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;

return static function (ContainerConfigurator $container) {
    $endMetadata = [
        'persist' => true,
        'redirectRoute' => ['routeName' => 'admin_operator_groups_list'],
    ];

    $container->extension('framework', [
        'workflows' => [
            'operator_group' => [
                'type' => 'state_machine',
                'initial_marking' => StateObject::STATE_CHOOSE_NAME,
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => [StateObject::class],
                'places' => [
                    StateObject::STATE_CHOOSE_NAME,
                    StateObject::STATE_PREVIEW,
                    StateObject::STATE_FINISH,
                ],
                'transitions' => [
                    'choose-name-to-preview' => [
                        'from' => StateObject::STATE_CHOOSE_NAME,
                        'to' =>  StateObject::STATE_PREVIEW,
                    ],
                    'preview-to-finish-add' => [
                        'from' => StateObject::STATE_PREVIEW,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => array_merge($endMetadata,
                            [
                                'notificationBanner' => (array) new NotificationBanner(
                                    'admin.operator-groups.add.success-notification.title',
                                    'admin.operator-groups.add.success-notification.heading',
                                    'admin.operator-groups.add.success-notification.content',
                                    ['style' => NotificationBanner::STYLE_SUCCESS]
                                ),
                            ]
                        ),
                        'guard' => "subject.getMode() === 'add'"
                    ],
                    'preview-to-finish-edit' => [
                        'from' => StateObject::STATE_PREVIEW,
                        'to' =>  StateObject::STATE_FINISH,
                        'metadata' => array_merge($endMetadata,
                            [
                                'notificationBanner' => (array) new NotificationBanner(
                                    'admin.operator-groups.edit.success-notification.title',
                                    'admin.operator-groups.edit.success-notification.heading',
                                    'admin.operator-groups.edit.success-notification.content',
                                    ['style' => NotificationBanner::STYLE_SUCCESS]
                                ),
                            ]
                        ),
                        'guard' => "subject.getMode() === 'edit'"
                    ],
                ]
            ],
        ],
    ]);
};