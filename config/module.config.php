<?php

return [
    'controllers' => [
        'invokables' => [
            'Sharing\Controller\Index' => 'Sharing\Controller\IndexController',
        ],
    ],
    'router' => [
        'routes' => [
                'embed-item' => [
                    'type' => 'Segment',
                    'options' => [
                        'route' => '/embed-item/:site-slug/:item-id',
                        'defaults' => [
                            '__NAMESPACE__' => 'Sharing\Controller',
                            'controller' => 'Index',
                            'action' => 'embedItem',
                        ],
                    ],
                ],
                'embed-page' => [
                    'type' => 'Segment',
                    'options' => [
                        'route' => '/embed-page/:page-id',
                        'defaults' => [
                            '__NAMESPACE__' => 'Sharing\Controller',
                            'controller' => 'Index',
                            'action' => 'embedPage',
                        ],
                    ],
                ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH.'/modules/Sharing/view',
        ],
    ],
];
