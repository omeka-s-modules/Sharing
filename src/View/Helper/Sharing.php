<?php declare(strict_types=1);

namespace Sharing\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Sharing extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/share-buttons';

    /**
     * Show sharing buttons of the current resource according to site settings.
     *
     * The current resources is an item, a media or a page.
     */
    public function __invoke(): string
    {
        $view = $this->getView();
        $plugins = $view->getHelperPluginManager();
        $siteSetting = $plugins->get('siteSetting');

        $enabledMethods = $siteSetting('sharing_methods');
        if (!$enabledMethods) {
            return '';
        }

        $assetUrl = $plugins->get('assetUrl');
        $currentSite = $plugins->get('currentSite');
        $headScript = $plugins->get('headScript');
        $siteSlug = $currentSite()->slug();

        if (in_array('fb', $enabledMethods)) {
            $script = <<<'JS'
            window.fbAsyncInit = function() {
                FB.init({
                    xfbml: true,
                    version: 'v2.5',
                });
            };
            (function(d, s, id){
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) {return;}
                js = d.createElement(s); js.id = id;
                js.src = '//connect.facebook.net/en_US/sdk.js';
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
            JS;
            $headScript->appendScript($script);
        }

        if (in_array('twitter', $enabledMethods)) {
            // FIXME In chrome, with or without defer/async, the button won't display in some cases in particular when preloaded and hidden in the single button.
            $headScript->appendFile('https://platform.twitter.com/widgets.js', 'text/javascript', ['id' => 'twitter-js', 'defer' => 'defer', 'async' => 'async']);
        }

        if (in_array('tumblr', $enabledMethods)) {
            $headScript->appendFile('https://assets.tumblr.com/share-button.js', 'text/javascript', ['id' => 'tumblr-js', 'defer' => 'defer', 'async' => 'async']);
        }

        if (in_array('pinterest', $enabledMethods)) {
            $headScript->appendFile('https://assets.pinterest.com/js/pinit.js', 'text/javascript', ['id' => 'pinterest', 'defer' => 'defer', 'async' => 'async']);
        }

        $headScript->appendFile($assetUrl('js/sharing.js', 'Sharing'), 'text/javascript', ['defer' => 'defer', 'async' => 'async']);
        $view->headLink()->appendStylesheet($assetUrl('css/sharing.css', 'Sharing'));

        return $view->partial(self::PARTIAL_NAME, [
            'enabledMethods' => $enabledMethods,
            'itemId' => isset($view->item) ? $view->item->id() : false,
            'mediaId' => isset($view->media) ? $view->media->id() : false,
            'pageId' => isset($view->page) ? $view->page->id() : false,
            'siteSlug' => $siteSlug,
            'displayAsButton' => (bool) $siteSetting('sharing_display_as_button'),
        ]);
    }
}
