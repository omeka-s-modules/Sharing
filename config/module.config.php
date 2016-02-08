<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Sharing\Controller\Index' => 'Sharing\Controller\IndexController',
        ),
    ),
    'router' => array(
        'routes' => array(
                'embed' => array(
                    'type' => 'Literal',
                    'options' => array(
                        'route'    => '/embed',
                        'defaults' => array(
                            '__NAMESPACE__' => 'Sharing\Controller',
                            'controller'    => 'Index',
                            'action'        => 'embed',
                        ),
                    ),
                )
        )
    ),
    'view_manager' => array(
        'template_path_stack'      => array(
            OMEKA_PATH . '/modules/Sharing/view',
        ),
    ),
);