<?php
namespace ActivityLog;

use Laminas\Router\Http;

return [
    'view_manager' => [
        'template_path_stack' => [
            sprintf('%s/../view', __DIR__),
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            sprintf('%s/../src/Entity', __DIR__),
        ],
        'proxy_paths' => [
            sprintf('%s/../data/doctrine-proxies', __DIR__),
        ],
    ],
    'service_manager' => [
        'factories' => [
            'ActivityLog\ActivityLog' => Service\ActivityLogFactory::class,
        ],
    ],
    'api_adapters' => [
        'invokables' => [
            'activity_log_event' => Api\Adapter\ActivityLogEventAdapter::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'ActivityLog\Controller\Admin\Index' => Controller\Admin\IndexController::class,
            'ActivityLog\Controller\Admin\Event' => Controller\Admin\EventController::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            'ActivityLog\Form\EventFilterForm' => Service\Form\EventFilterFormFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [],
    ],
    'view_helpers' => [
        'factories' => [],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Activity Log', // @translate
                'route' => 'admin/activity-log',
                'controller' => 'index',
                'action' => 'index',
                'resource' => 'ActivityLog\Controller\Admin\Index',
                'useRouteMatch' => true,
                'pages' => [
                    [
                        'route' => 'admin/activity-log/default',
                        'controller' => 'event',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'activity-log' => [
                        'type' => Http\Literal::class,
                        'options' => [
                            'route' => '/activity-log',
                            'defaults' => [
                                '__NAMESPACE__' => 'ActivityLog\Controller\Admin',
                                'controller' => 'index',
                                'action' => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'default' => [
                                'type' => Http\Segment::class,
                                'options' => [
                                    'route' => '/:controller[/:action]',
                                    'constraints' => [
                                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'index',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
