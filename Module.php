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
    
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\SiteAdmin\IndexController',
            'site_settings.form',
            [$this, 'addSiteEnableCheckbox']
        );
        
        $sharedEventManager->attach(
                array('Omeka\Controller\Site\Item',
                      'Omeka\Controller\Site\Index',
                      'Omeka\Controller\Site\Page'
                      ),
                'view.show.after',
                array($this, 'viewShowAfter')
                );
        
        $sharedEventManager->attach(
                array('Omeka\Controller\Site\Item',
                      'Omeka\Controller\Site\Index',
                      'Omeka\Controller\Site\Page'
                      ),
                'view.show.after',
                array($this, 'insertOpenGraphData')
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
            'attributes' => [
                'value' => (bool) $this->siteSettings->get('sharing_enable', false)
            ]
        ]);
        echo $this->siteSettings->get('sharing_enable');
    }
    
    public function insertOpenGraphData($event)
    {
        $view = $event->getTarget();
        $escape = $view->plugin('escapeHtml');
        if (isset($view->item)) {
            $description = $escape($view->item->displayDescription());
            $view->headMeta()->appendProperty('og:description', $description);
            if ($primaryMedia = $view->item->primaryMedia()) {
                $image = $escape($primaryMedia->thumbnailUrl('square'));
                $view->headMeta()->appendProperty('og:image', $image);
            }
        }
        $view->headMeta()->appendProperty('og:title', $view->headTitle()->renderTitle());
        $view->headMeta()->appendProperty('og:type', 'website');
        $view->headMeta()->appendProperty('og:url', $view->serverUrl(true));
    }
    
    public function insertJavascript($event)
    {
        if ($this->siteSettings->get('sharing_enable')) {
            echo "<script type='text/javascript'> alert('hello'); </script>";
        }
    }
    
    public function viewShowAfter($event)
    {
        $view = $event->getTarget();
        $escape = $view->plugin('escapeHtml');
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        echo $view->partial('share-buttons', array('escape' => $escape, 'translator' => $translator));
    }
}
