<?php
namespace Sharing;

use Omeka\Module\AbstractModule;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\EventManager\SharedEventManagerInterface;

class Module extends AbstractModule
{
    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\SiteAdmin\IndexController',
            'site_settings.form',
            [$this, 'addSiteEnableCheckbox']
        );
    }
    
    public function addSiteEnableCheckbox()
    {
        echo 'what?';
    }
}
