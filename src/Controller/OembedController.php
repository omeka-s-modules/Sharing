<?php
namespace Sharing\Controller;

use Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Uri\Http as HttpUrl;
use Laminas\View\Model\JsonModel;

class OembedController extends AbstractActionController
{
    public function indexAction()
    {
        // @see https://oembed.com/
        $response = $this->getResponse();
        $format = $this->params()->fromQuery('format', 'json');
        if ('json' !== $format) {
            // Invalid format. Return 501 Not Implemented.
            $response->setStatusCode(501);
            return $response;
        }
        $url = new HttpUrl($this->params()->fromQuery('url'));
        if (!$url->isValid()) {
            // Invalid URL passed in url query parameter. Return 404 Not Found.
            $response->setStatusCode(404);
            return $response;
        }

        // Build the oEmbed JSON response.
        $oembed = [
            'type' => 'rich',
            'version' => '1.0',
            'title' => null,
            'html' => null,
        ];
        $escapeHtml = $this->viewHelpers()->get('escapeHtml');

        // This pattern matches public resource page URLs.
        $isItemMatch = preg_match('#^.+/s/(.+)/item/(\d+)$#i', $url->getPath(), $itemMatches);
        $isMediaMatch = preg_match('#^.+/s/(.+)/media/(\d+)$#i', $url->getPath(), $mediaMatches);
        $isPageMatch = preg_match('#^.+/s/(.+)/page/(.+)$#i', $url->getPath(), $pageMatches);

        // Handle a public item page.
        if ($isItemMatch) {
            [$path, $siteSlug, $itemId] = $itemMatches;
            try {
                $site = $this->api()->searchOne('sites', ['slug' => $siteSlug])->getContent();
                $item = $this->api()->read('items', $itemId)->getContent();
            } catch (Exception $e) {
                $response->setStatusCode(404);
                return $response;
            }
            $oembed['title'] = sprintf('%s · %s', $item->displayTitle(), $site->title());
            $embedUrl = $this->url()->fromRoute('embed-item', ['site-slug' => $site->slug(), 'item-id' => $item->id()], ['force_canonical' => true]);
            $oembed['html'] = sprintf('<iframe src="%s"></iframe>', $escapeHtml($embedUrl));
            if ($primaryMedia = $item->primaryMedia()) {
                $oembed['thumbnail_url'] = $primaryMedia->thumbnailUrl('square');
                $oembed['thumbnail_width'] = 200;
                $oembed['thumbnail_height'] = 200;
            }
            // Handle a public media page.
        } elseif ($isMediaMatch) {
            [$path, $siteSlug, $mediaId] = $mediaMatches;
            try {
                $site = $this->api()->searchOne('sites', ['slug' => $siteSlug])->getContent();
                $media = $this->api()->read('media', $mediaId)->getContent();
            } catch (Exception $e) {
                $response->setStatusCode(404);
                return $response;
            }
            $oembed['title'] = sprintf('%s · %s', $media->displayTitle(), $site->title());
            $embedUrl = $this->url()->fromRoute('embed-media', ['site-slug' => $site->slug(), 'media-id' => $media->id()], ['force_canonical' => true]);
            $oembed['html'] = sprintf('<iframe src="%s"></iframe>', $escapeHtml($embedUrl));
            if ($primaryMedia = $media->primaryMedia()) {
                $oembed['thumbnail_url'] = $primaryMedia->thumbnailUrl('square');
                $oembed['thumbnail_width'] = 200;
                $oembed['thumbnail_height'] = 200;
            }
            // Handle a public site page.
        } elseif ($isPageMatch) {
            [$path, $siteSlug, $pageSlug] = $pageMatches;
            try {
                $site = $this->api()->searchOne('sites', ['slug' => $siteSlug])->getContent();
                $sitePage = $this->api()->searchOne('site_pages', ['site_id' => $site->id(), 'slug' => $pageSlug])->getContent();
            } catch (Exception $e) {
                $response->setStatusCode(404);
                return $response;
            }
            $oembed['title'] = sprintf('%s · %s', $sitePage->title(), $sitePage->site()->title());
            $embedUrl = $this->url()->fromRoute('embed-page', ['page-id' => $sitePage->id()], ['force_canonical' => true]);
            $oembed['html'] = sprintf('<iframe src="%s"></iframe>', $escapeHtml($embedUrl));
            foreach ($sitePage->blocks() as $block) {
                foreach ($block->attachments() as $attachment) {
                    $item = $attachment->item();
                    if ($primaryMedia = $resource->primaryMedia()) {
                        $oembed['thumbnail_url'] = $primaryMedia->thumbnailUrl('square');
                        $oembed['thumbnail_width'] = 200;
                        $oembed['thumbnail_height'] = 200;
                        break 2;
                    }
                }
            }
        } else {
            // Invalid resource URL passed in url query parameter. Return 404 Not Found.
            $response->setStatusCode(404);
            return $response;
        }

        $jsonModel = new JsonModel($oembed);
        $jsonModel->setOption('prettyPrint', true);
        return $jsonModel;
    }
}
