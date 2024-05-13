<?php
namespace Sharing;

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
    'view_helpers' => [
        'invokables' => [
            'sharing' => View\Helper\Sharing::class,
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            'sharing' => Site\ResourcePageBlockLayout\Sharing::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Sharing\Controller\Index' => Controller\IndexController::class,
            'Sharing\Controller\Oembed' => Controller\OembedController::class,
        ],
    ],
    'router' => [
        'routes' => [
            'embed-item' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/embed-item/:site-slug/:item-id',
                    'defaults' => [
                        '__NAMESPACE__' => 'Sharing\Controller',
                        'controller' => 'Index',
                        'action' => 'embedItem',
                    ],
                ],
            ],
            'embed-media' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/embed-media/:site-slug/:media-id',
                    'defaults' => [
                        '__NAMESPACE__' => 'Sharing\Controller',
                        'controller' => 'Index',
                        'action' => 'embedMedia',
                    ],
                ],
            ],
            'embed-page' => [
                'type' => Http\Segment::class,
                'options' => [
                    'route' => '/embed-page/:page-id',
                    'defaults' => [
                        '__NAMESPACE__' => 'Sharing\Controller',
                        'controller' => 'Index',
                        'action' => 'embedPage',
                    ],
                ],
            ],
            'oembed' => [
                'type' => Http\Literal::class,
                'options' => [
                    'route' => '/oembed',
                    'defaults' => [
                        'controller' => 'Sharing\Controller\Oembed',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => sprintf('%s/../language', __DIR__),
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
];
