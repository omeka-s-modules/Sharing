<?php

namespace Sharing\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function embedItemAction()
    {
        $itemId = $this->params('item-id');
        $siteSlug = $this->params('site-slug');
        $response = $this->api()->read('items', $itemId);
        $item = $response->getContent();

        $view = new ViewModel();
        $view->setTerminal(true);
        $view->setVariable('item', $item);
        $view->setVariable('siteSlug', $siteSlug);

        return $view;
    }

    public function embedPageAction()
    {
        $pageId = $this->params('page-id');
        $siteSlug = $this->params('site-slug');
        $response = $this->api()->read('site_pages', $pageId);
        $page = $response->getContent();

        $view = new ViewModel();
        $view->setTerminal(true);
        $view->setVariable('page', $page);
        $view->setVariable('siteSlug', $siteSlug);

        return $view;
    }
}
