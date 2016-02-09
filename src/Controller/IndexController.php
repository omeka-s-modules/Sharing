<?php

namespace Sharing\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function embedAction()
    {
        $data = $this->params('item-id');
        $itemId = $this->params('item-id');
        //$itemId = 3952; //@todo make this real. just testing
        $response = $this->api()->read('items', $itemId);
        $item = $response->getContent();
        
        $view = new ViewModel;
        $view->setVariable('item', $item);
        return $view;
    }
}