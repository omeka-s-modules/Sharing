<?php

namespace Sharing;

use Omeka\Module\AbstractModule;
use Laminas\Form\Fieldset;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, 'Sharing\Controller\Index');
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $controllerPluginManager = $serviceLocator->get('ControllerPluginManager');
        $messenger = $controllerPluginManager->get('messenger');
        $messenger->addSuccess('Sharing options are site-specific. Site owners will need to set the options for their sites.'); // @translate
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_elements',
            [$this, 'addSiteEnableCheckbox']
        );

        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_input_filters',
            [$this, 'addSiteSettingsFilters']
        );

        $controllers = [
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\Page',
        ];

        foreach ($controllers as $controller) {
            $sharedEventManager->attach(
                $controller,
                'view.show.before',
                [$this, 'viewShow']
                );

            $sharedEventManager->attach(
                $controller,
                'view.show.after',
                [$this, 'viewShow']
                );
        }

        $sharedEventManager->attach(
                'Omeka\Controller\Site\Item',
                'view.show.after',
                [$this, 'insertOpenGraphData']
                );

        $sharedEventManager->attach(
                'Omeka\Controller\Site\Index',
                'view.show.after',
                [$this, 'insertOpenGraphData']
                );

        $sharedEventManager->attach(
               'Omeka\Controller\Site\Page',
                'view.show.after',
                [$this, 'insertOpenGraphData']
                );
    }

    public function addSiteSettingsFilters($event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->get('sharing')->add([
                    'name' => 'sharing_methods',
                    'required' => false,
                ]);
    }

    public function addSiteEnableCheckbox($event)
    {
        $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
        $form = $event->getTarget();

        $fieldset = new Fieldset('sharing');
        $fieldset->setLabel('Sharing'); // @translate

        $enabledMethods = $siteSettings->get('sharing_methods', []);
        $placement = $siteSettings->get('sharing_placement', 'view.show.before');
        $fieldset->add([
            'name' => 'sharing_methods',
            'type' => 'multiCheckbox',
            'options' => [
                'label' => 'Enable Sharing module for these methods', // @translate
                'value_options' => [
                    'fb' => [
                                    'label' => 'Facebook', // @translate
                                    'value' => 'fb',
                                    'selected' => in_array('fb', $enabledMethods),
                                    ],
                    'twitter' => [
                                    'label' => 'Twitter', // @translate
                                    'value' => 'twitter',
                                    'selected' => in_array('twitter', $enabledMethods),
                                   ],
                    'tumblr' => [
                                    'label' => 'Tumblr', // @translate
                                    'value' => 'tumblr',
                                    'selected' => in_array('tumblr', $enabledMethods),
                                   ],
                    'pinterest' => [
                                    'label' => 'Pinterest', // @translate
                                    'value' => 'pinterest',
                                    'selected' => in_array('pinterest', $enabledMethods),
                                   ],
                    'email' => [
                                    'label' => 'Email', // @translate
                                    'value' => 'email',
                                    'selected' => in_array('email', $enabledMethods),
                                   ],
                    'embed' => [
                                    'label' => 'Embed codes', // @translate
                                    'value' => 'embed',
                                    'selected' => in_array('embed', $enabledMethods),
                                   ],
                ],
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);

        $fieldset->add([
            'name' => 'sharing_placement',
            'type' => 'radio',
            'options' => [
                'label' => "Sharing buttons placement on the page", // @translate
                'value_options' => [
                    'top' => [
                        'label' => 'Top', // @translate
                        'value' => 'view.show.before',
                    ],

                    'bottom' => [
                        'label' => 'Bottom', //@translate
                        'value' => 'view.show.after',
                    ],

                ],
            ],
            'attributes' => [
                'required' => false,
                'value' => $placement,
            ],
        ]);
        $form->add($fieldset);
    }

    public function insertOpenGraphData($event)
    {
        $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
        $routeMatch = $this->getServiceLocator()->get('Application')
                            ->getMvcEvent()->getRouteMatch();
        $controller = $routeMatch->getParam('controller');
        $view = $event->getTarget();
        $escape = $view->plugin('escapeHtml');
        $description = false;
        $image = false;
        switch ($controller) {
                case 'Omeka\Controller\Site\Item':
                    $description = $escape($view->item->displayDescription());
                    $view->headMeta()->appendProperty('og:description', $description);
                    if ($primaryMedia = $view->item->primaryMedia()) {
                        $image = $escape($primaryMedia->thumbnailUrl('large'));
                        $view->headMeta()->appendProperty('og:image', $image);
                    }
                break;

                case 'Omeka\Controller\Site\Page':
                    $blocks = $view->page->blocks();
                    foreach ($blocks as $block) {
                        $attachments = $block->attachments();
                        foreach ($attachments as $attachment) {
                            $item = $attachment->item();
                            if ($item && ($primaryMedia = $item->primaryMedia())) {
                                $image = $escape($primaryMedia->thumbnailUrl('large'));
                                break 2;
                            }
                        }
                    }
                break;
            }
        $view->headTitle()->setSeparator(' · ');
        $pageTitle = $view->headTitle()->renderTitle() . ' · ' . $view->setting('installation_title', 'Omeka S');
        $view->headMeta()->appendProperty('og:title', $pageTitle);
        $view->headMeta()->appendProperty('og:type', 'website');
        $view->headMeta()->appendProperty('og:url', $view->serverUrl(true));
        if ($description) {
            $view->headMeta()->appendProperty('og:description', $description);
        }

        if ($image) {
            $view->headMeta()->appendProperty('og:image', $image);
        }
    }

    public function viewShow($event)
    {
        $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
        $enabledMethods = $siteSettings->get('sharing_methods');
        $placement = $siteSettings->get('sharing_placement', 'view.show.before');
        $eventName = $event->getName();
        if (!empty($enabledMethods) && ($eventName == $placement)) {
            $view = $event->getTarget();
            $view->headScript()->appendFile('https://platform.twitter.com/widgets.js');
            $view->headScript()->appendFile($view->assetUrl('js/sharing.js', 'Sharing'));
            $view->headLink()->appendStylesheet($view->assetUrl('css/sharing.css', 'Sharing'));
            $escape = $view->plugin('escapeHtml');
            $translator = $this->getServiceLocator()->get('MvcTranslator');
            $siteSlug = $this->getServiceLocator()->get('Application')
                ->getMvcEvent()->getRouteMatch()->getParam('site-slug');

            echo $view->partial('share-buttons',
                    ['escape' => $escape,
                          'translator' => $translator,
                          'enabledMethods' => $enabledMethods,
                          'itemId' => isset($view->item) ? $view->item->id() : false,
                          'pageId' => isset($view->page) ? $view->page->id() : false,
                          'siteSlug' => $siteSlug,
                            ]
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

            $pinterestJavascript = '
                <script
                    type="text/javascript"
                    async defer
                    src="//assets.pinterest.com/js/pinit.js"
                ></script>
            ';

            $tumblrJavascript = '
                <script id="tumblr-js" async src="https://assets.tumblr.com/share-button.js"></script>
            ';

            foreach ($enabledMethods as $method) {
                $js = $method . 'Javascript';
                if (isset($$js)) {
                    echo $$js;
                }
            }
        }
    }
}
