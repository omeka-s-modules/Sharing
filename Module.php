<?php
namespace Sharing;

use Omeka\Module\AbstractModule;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;

class Module extends AbstractModule
{
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
        $siteSettings = $this->getServiceLocator()->get('Omeka\SiteSettings');
        $form = $event->getParam('form');
        $translator = $form->getTranslator();
        
        $enabledMethods = $siteSettings->get('sharing_methods', array());
        $form->add([
            'name' => 'sharing_methods',
            'type' => 'multi_checkbox',
            'options' => [
                'label' => $translator->translate('Enable Sharing module for these methods'),
                'value_options' => [
                    'fb'        => $translator->translate('Facebook'),
                    'twitter'   => $translator->translate('Twitter'),
                    'tumblr'    => $translator->translate('Tumblr'),
                    'pinterest' => $translator->translate('Pinterest'),
                    'email' => $translator->translate('Email'),
                ]

            ],
        ]);

    }
    
    public function insertOpenGraphData($event)
    {
        $siteSettings = $this->getServiceLocator()->get('Omeka\SiteSettings');
        if ($siteSettings->get('sharing_enable')) {
            $routeMatch = $this->getServiceLocator()->get('Application')
                            ->getMvcEvent()->getRouteMatch();
            $controller = $routeMatch->getParam('controller');
            
            $view = $event->getTarget();
            $escape = $view->plugin('escapeHtml');
            switch  ($controller) {
                case 'Omeka\Controller\Site\Item' :
                    $description = $escape($view->item->displayDescription());
                    $view->headMeta()->appendProperty('og:description', $description);
                    if ($primaryMedia = $view->item->primaryMedia()) {
                        $image = $escape($primaryMedia->thumbnailUrl('square'));
                        $view->headMeta()->appendProperty('og:image', $image);
                    }
                break;
                    
                case 'Omeka\Controller\Site\Index':
                    $description = '';
                    $image = '';
                    $view->headMeta()->appendProperty('og:description', $description);
                    $view->headMeta()->appendProperty('og:image', $image);
                break;
                    
                case 'Omeka\Controller\Site\Page':
                    // need to figure out how to handle the different block types and finding an image
                    $description = '';
                    $block = $view->page->blocks()[0];
                    $attachment = $block->attachments()[0];
                    $item = $attachment->item();
                    if ($primaryMedia = $item->primaryMedia()) {
                        $image = $escape($primaryMedia->thumbnailUrl('square'));
                        $view->headMeta()->appendProperty('og:image', $image);
                    }
                    $image = '';
                    $view->headMeta()->appendProperty('og:description', $description);
                    $view->headMeta()->appendProperty('og:image', $image);
                break;
            }
            $view->headMeta()->appendProperty('og:title', $view->headTitle()->renderTitle());
            $view->headMeta()->appendProperty('og:type', 'website');
            $view->headMeta()->appendProperty('og:url', $view->serverUrl(true));
        }
    }
    
    public function viewShowAfter($event)
    {
        
        $siteSettings = $this->getServiceLocator()->get('Omeka\SiteSettings');
        $enabledMethods = $siteSettings->get('sharing_methods');
        if (! empty($enabledMethods)) {
            
            $view = $event->getTarget();
            $view->headScript()->appendFile('https://platform.twitter.com/widgets.js');
            $view->headLink()->appendStylesheet($view->assetUrl('css/sharing.css', 'Sharing'));
            $escape = $view->plugin('escapeHtml');
            $translator = $this->getServiceLocator()->get('MvcTranslator');
            echo $view->partial('share-buttons',
                    array('escape' => $escape,
                          'translator' => $translator,
                          'enabledMethods' => $enabledMethods)
                    );
            
            $fbJavascript = "
            <script>
              window.fbAsyncInit = function() {
                FB.init({
                  xfbml      : true,
                  version    : 'v2.5'
                });
              };
            
              (function(d, s, id){
                 var js, fjs = d.getElementsByTagName(s)[0];
                 if (d.getElementById(id)) {return;}
                 js = d.createElement(s); js.id = id;
                 js.src = '//connect.facebook.net/en_US/sdk.js';
                 fjs.parentNode.insertBefore(js, fjs);
               }(document, 'script', 'facebook-jssdk'));
            </script>
            ";
            
            $pinterstJavascript = '
            
                <script
                    type="text/javascript"
                    async defer
                    src="//assets.pinterest.com/js/pinit.js"
                ></script>
            
            ';
            
            $tumblrJavascript = '
                <script id="tumblr-js" async src="https://assets.tumblr.com/share-button.js"></script>
            ';
            
            foreach($enabledMethods as $method) {
                $js = $method . 'Javascript';
                if (isset($$js)) {
                    echo $$js;
                }
            }
        }
    }
}
