<?php

namespace Sharing\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function embedAction()
    {
        $itemId = $this->params('item-id');
        $siteSlug = $this->params('site-slug');
        $response = $this->api()->read('items', $itemId);
        $item = $response->getContent();
        
        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('item', $item);
        $view->setVariable('siteSlug', $siteSlug);
        return $view;
    }
}