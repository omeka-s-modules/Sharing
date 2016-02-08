<?php

namespace Sharing\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function embedAction()
    {
        $data = $this->params()->fromPost();
        $itemId = $data['item_id'];
        $itemId = 3952; //@todo make this real. just testing
        $response = $this->api()->find('item', $itemId);
        $item = $response->getContent();
        
        $view = new ViewModel;
        $view->setVariable('item', $item);
        return $view;
    }
}