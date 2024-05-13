<?php declare(strict_types=1);

namespace Sharing\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;

class Sharing extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/sharing';

    public function getLabel()
    {
        return 'Sharing'; // @translate
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        return '<p>'
            . $view->translate('Display the share buttons of the current page according to site settings.') // @translate
            . '</p>';
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $plugins = $view->getHelperPluginManager();
        $sharing = $plugins->get('sharing');

        $page = $block->page();
        $site = $page->site();

        return $view->partial(self::PARTIAL_NAME, [
            'site' => $site,
            'page' => $page,
            'block' => $block,
        ]);
    }
}
