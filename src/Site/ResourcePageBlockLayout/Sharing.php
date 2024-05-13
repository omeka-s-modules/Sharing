<?php declare(strict_types=1);

namespace Sharing\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class Sharing implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Sharing'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        $vars = [
            'site' => $view->layout()->site,
            'resource' => $resource,
        ];

        $isMedia = $resource->resourceName() === 'media';
        if ($isMedia) {
            $vars['item'] = $resource->item();
            $vars['media'] = $resource;
        } else {
            $vars['item'] = $resource;
        }

        return $view->partial('common/resource-page-block-layout/sharing', $vars);
    }
}
