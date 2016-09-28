<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Sharing\Controller\Index' => 'Sharing\Controller\IndexController',
        ),
    ),
    'router' => array(
        'routes' => array(
                'embed-item' => array(
                    'type' => 'Segment',
                    'options' => array(
                        'route' => '/embed-item/:site-slug/:item-id',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Sharing\Controller',
                            'controller' => 'Index',
                            'action' => 'embedItem',
                        ),
                    ),
                ),
                'embed-page' => array(
                    'type' => 'Segment',
                    'options' => array(
                        'route' => '/embed-page/:page-id',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Sharing\Controller',
                            'controller' => 'Index',
                            'action' => 'embedPage',
                        ),
                    ),
                ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            OMEKA_PATH.'/modules/Sharing/view',
        ),
    ),
);
