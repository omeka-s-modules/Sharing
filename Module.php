<?php

namespace Sharing;

use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, ['Sharing\Controller\Index', 'Sharing\Controller\Oembed']);
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $messenger = $serviceLocator->get('ControllerPluginManager')->get('messenger');
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

        $resources = [
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\Page',
        ];
        foreach ($resources as $resource) {
            $sharedEventManager->attach(
                $resource,
                'view.show.before',
                [$this, 'viewShow']
            );
            $sharedEventManager->attach(
                $resource,
                'view.show.after',
                [$this, 'viewShow']
            );
        }

        // Add discoverable oEmbed head links to public resource pages.
        $resources = [
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\Media',
        ];
        foreach ($resources as $resource) {
            $sharedEventManager->attach(
                $resource,
                'view.show.after',
                function (Event $event) {
                    $view = $event->getTarget();
                    $resourceUrl = $view->url(null, [], ['force_canonical' => true], true);
                    $resourceTitle = $view->resource->displayTitle();
                    $href = $view->url('oembed', [], ['force_canonical' => true, 'query' => ['url' => $resourceUrl]]);
                    $view->headLink([
                        'rel' => 'alternate',
                        'type' => 'application/json+oembed',
                        'title' => $resourceTitle,
                        'href' => $href,
                    ]);
                }
            );
        }
    }

    public function addSiteSettingsFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->add([
            'name' => 'sharing_methods',
            'required' => false,
        ]);
    }

    public function addSiteEnableCheckbox(Event $event)
    {
        $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
        $form = $event->getTarget();

        $groups = $form->getOption('element_groups');
        $groups['sharing'] = 'Sharing'; // @translate
        $form->setOption('element_groups', $groups);

        $enabledMethods = $siteSettings->get('sharing_methods', []);
        $placement = $siteSettings->get('sharing_placement', 'view.show.before');
        $form->add([
            'name' => 'sharing_methods',
            'type' => 'multiCheckbox',
            'options' => [
                'element_group' => 'sharing',
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

        $form->add([
            'name' => 'sharing_placement',
            'type' => 'radio',
            'options' => [
                'element_group' => 'sharing',
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
    }

    public function insertOpenGraphData(Event $event)
    {
        $status = $this->getServiceLocator()->get('Omeka\Status');
        $view = $event->getTarget();
        $controller = $status->getRouteMatch()->getParam('controller');

        $view->headTitle()->setSeparator(' · ');
        $metaProperties = [
            'og:type' => 'website',
            'og:title' => sprintf('%s · %s', $view->headTitle()->renderTitle(), $view->setting('installation_title', 'Omeka S')),
            'og:description' => null,
            'og:url' => $view->serverUrl(true),
            'og:image' => null,
        ];
        switch ($controller) {
            case 'Omeka\Controller\Site\Item':
                $metaProperties['og:description'] = $view->item->displayDescription();
                if ($primaryMedia = $view->item->primaryMedia()) {
                    $metaProperties['og:image'] = $primaryMedia->thumbnailUrl('large');
                }
            break;
            case 'Omeka\Controller\Site\Page':
                foreach ($view->page->blocks() as $block) {
                    foreach ($block->attachments() as $attachment) {
                        $item = $attachment->item();
                        if ($item && ($primaryMedia = $item->primaryMedia())) {
                            $metaProperties['og:image'] = $primaryMedia->thumbnailUrl('large');
                            break 2;
                        }
                    }
                }
            break;
        }
        foreach ($metaProperties as $metaProperty => $metaContent) {
            if (null !== $metaContent) {
                $view->headMeta()->appendProperty($metaProperty, $metaContent);
            }
        }
    }

    public function viewShow(Event $event)
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
            $siteSlug = $this->getServiceLocator()->get('Application')
                ->getMvcEvent()->getRouteMatch()->getParam('site-slug');
            echo $view->partial('share-buttons', [
                'enabledMethods' => $enabledMethods,
                'itemId' => isset($view->item) ? $view->item->id() : false,
                'pageId' => isset($view->page) ? $view->page->id() : false,
                'siteSlug' => $siteSlug,
            ]);
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
