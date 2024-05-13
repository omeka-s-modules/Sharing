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
        $siteSlug = $currentSite()->slug();

        if (in_array('twitter', $enabledMethods)) {
            $view->headScript()->appendFile('https://platform.twitter.com/widgets.js');
        }
        $view->headScript()->appendFile($assetUrl('js/sharing.js', 'Sharing'));
        $view->headLink()->appendStylesheet($assetUrl('css/sharing.css', 'Sharing'));

        $html = $view->partial(self::PARTIAL_NAME, [
            'enabledMethods' => $enabledMethods,
            'itemId' => isset($view->item) ? $view->item->id() : false,
            'mediaId' => isset($view->media) ? $view->media->id() : false,
            'pageId' => isset($view->page) ? $view->page->id() : false,
            'siteSlug' => $siteSlug,
            'displayAsButton' => (bool) $siteSetting('sharing_display_as_button'),
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
                $html .= $$js;
            }
        }

        return $html;
    }
}
