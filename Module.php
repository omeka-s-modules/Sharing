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
        // Add site settings.
        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_elements',
            [$this, 'addSiteSettingsForm']
        );
        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_input_filters',
            [$this, 'addSiteSettingsFormFilters']
        );

        // Add sharing methods to public pages.
        $resources = [
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\Media',
            'Omeka\Controller\Site\Page',
        ];
        foreach ($resources as $resource) {
            $sharedEventManager->attach(
                $resource,
                'view.show.before',
                [$this, 'addSharingMethods']
            );
            $sharedEventManager->attach(
                $resource,
                'view.show.after',
                [$this, 'addSharingMethods']
            );
        }

        // Add Open Graph head meta to public pages.
        $resources = [
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\Media',
            'Omeka\Controller\Site\Index',
            'Omeka\Controller\Site\Page',
        ];
        foreach ($resources as $resource) {
            $sharedEventManager->attach(
                $resource,
                'view.show.after',
                [$this, 'addOpenGraphHeadMeta']
            );
        }

        // Add discoverable oEmbed head links to public pages.
        $resources = [
            'Omeka\Controller\Site\Item',
            'Omeka\Controller\Site\Media',
            'Omeka\Controller\Site\Page',
        ];
        foreach ($resources as $resource) {
            $sharedEventManager->attach(
                $resource,
                'view.show.after',
                [$this, 'addOembedHeadLink']
            );
        }
    }

    public function addSiteSettingsForm(Event $event)
    {
        $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
        $form = $event->getTarget();

        $groups = $form->getOption('element_groups');
        $groups['sharing'] = 'Sharing'; // @translate
        $form->setOption('element_groups', $groups);

        $enabledMethods = $siteSettings->get('sharing_methods', []);
        $placement = $siteSettings->get('sharing_placement', '');
        $display = (int) $siteSettings->get('sharing_display_as_button', 0);

        $form->add([
            'name' => 'sharing_methods',
            'type' => 'multiCheckbox',
            'options' => [
                'element_group' => 'sharing',
                'label' => 'Sharing buttons', // @translate
                'info' => 'Select which sharing buttons to display.', // @translate
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
                'label' => "Sharing buttons placement", // @translate
                'info' => 'Select "Top" or "Bottom" to place the buttons on site pages, item show pages, and media show pages. Select "Using blocks" to place the buttons using page blocks and resource blocks', // @translate
                'value_options' => [
                    'view.show.before' => 'Top', // @translate
                    'view.show.after' => 'Bottom', //@translate
                    '' => 'Using blocks', // @translate
                ],
            ],
            'attributes' => [
                'required' => false,
                'value' => $placement,
            ],
        ]);

        $form->add([
            'name' => 'sharing_display_as_button',
            'type' => 'checkbox',
            'options' => [
                'element_group' => 'sharing',
                'label' => 'Single button', // @translate
                'info' => "Check to display all sharing buttons as a single share button", // @translate
            ],
            'attributes' => [
                'id' => 'sharing_display_as_button',
                'required' => false,
                'value' => $display,
            ],
        ]);
    }

    public function addSiteSettingsFormFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->add([
            'name' => 'sharing_methods',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'sharing_placement',
            'required' => false,
        ]);
    }

    public function addSharingMethods(Event $event)
    {
        $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
        $enabledMethods = $siteSettings->get('sharing_methods', []);
        if (!count($enabledMethods)) {
            return;
        }

        $placement = $siteSettings->get('sharing_placement', 'view.show.before');
        $eventName = $event->getName();
        if ($eventName === $placement) {
            /** @see \Sharing\View\Helper\Sharing */
            $view = $event->getTarget();
            echo $view->sharing();
        }
    }

    /**
     * Add Open Graph head meta.
     *
     * @see https://ogp.me/
     * @param Event $event
     */
    public function addOpenGraphHeadMeta(Event $event)
    {
        $status = $this->getServiceLocator()->get('Omeka\Status');
        $view = $event->getTarget();
        $controller = $status->getRouteMatch()->getParam('controller');

        $metaProperties = [
            'og:type' => 'website',
            'og:site_name' => $view->site->title(),
            'og:title' => $view->headTitle()->renderTitle(),
            'og:url' => $view->serverUrl(true),
        ];
        switch ($controller) {
            case 'Omeka\Controller\Site\Item':
            case 'Omeka\Controller\Site\Media':
                $metaProperties['og:description'] = $view->resource->displayDescription();
                if ($primaryMedia = $view->resource->primaryMedia()) {
                    $metaProperties['og:image'] = $primaryMedia->thumbnailUrl('large');
                    $mediaType = $primaryMedia->mediaType();
                    if ($mediaType === null) {
                        break;
                    }
                    $mediaMainType = strstr($mediaType, '/', true);
                    switch ($mediaMainType) {
                        case 'audio':
                            $metaProperties['og:audio'] = $primaryMedia->originalUrl();
                            break;
                        case 'video':
                            $metaProperties['og:video'] = $primaryMedia->originalUrl();
                            break;
                    }
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
            if ($metaContent) {
                $view->headMeta()->appendProperty($metaProperty, $metaContent);
            }
        }
    }

    /**
     * Add oEmbed head link.
     *
     * @see https://oembed.com/
     * @param Event $event
     */
    public function addOembedHeadLink(Event $event)
    {
        $view = $event->getTarget();

        $href = $view->url('oembed', [], ['force_canonical' => true, 'query' => ['url' => $view->serverUrl(true)]]);
        $view->headLink([
            'rel' => 'alternate',
            'type' => 'application/json+oembed',
            'title' => $view->headTitle()->renderTitle(),
            'href' => $href,
        ]);
    }
}
