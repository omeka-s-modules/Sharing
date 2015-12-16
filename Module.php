<?php
namespace Sharing;

use Omeka\Module\AbstractModule;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;

class Module extends AbstractModule
{
    protected $siteSettings;
    
    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->siteSettings = $this->getServiceLocator()->get('Omeka\SiteSettings');
    }
    
    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\SiteAdmin\IndexController',
            'site_settings.form',
            [$this, 'addSiteEnableCheckbox']
        );
        
        $sharedEventManager->attach(
                array('Omeka\Controller\Site\Item', 'Omeka\Controller\Site\Page'),
                'view.show.after',
                array($this, 'insertJavascript')
                );
    }
    
    public function addSiteEnableCheckbox($event)
    {
        $form = $event->getParam('form');
        $translator = $form->getTranslator();
        $form->add([
            'name' => 'sharing_enable',
            'type' => 'checkbox',
            'options' => [
                'label' => $translator->translate('Enable the Sharing module for this site.'),
            ],
            'attributes' => array('value' => $this->siteSettings->get('sharing_enable'))
        ]);
    }
    
    public function insertJavascript($event)
    {
        if ($this->siteSettings->get('sharing_enable')) {
            echo "<script type='text/javascript'> alert('hello'); </script>";
        }
    } 
}
