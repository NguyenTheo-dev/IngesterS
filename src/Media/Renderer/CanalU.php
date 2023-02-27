<?php
namespace IngesterS\Media\Renderer;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\Renderer\RendererInterface;
use Laminas\Uri\Http as HttpUri;
use Laminas\View\Renderer\PhpRenderer;

class CanalU implements RendererInterface
{
    const WIDTH = 560;
    const HEIGHT = 315;
    const ALLOWFULLSCREEN = true;

    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = []) {

        if (!isset($options['width'])) {
            $options['width'] = self::WIDTH;
        }
        if (!isset($options['height'])) {
            $options['height'] = self::HEIGHT;
        }
        if (!isset($options['allowfullscreen'])) {
            $options['allowfullscreen'] = self::ALLOWFULLSCREEN;
        }

        // Compose the YouTube embed URL and build the markup.
        $data = $media->mediaData();
        $url = new HttpUri(sprintf('https://www.canal-u.tv/chaines/%s/embed/%s', $data['channel'], $data['id']));
        $query = [];
        if (isset($data['start'])) {
            $query['t'] = $data['start'];
        }
        $url->setQuery($query);
        $embed = sprintf(
            '<iframe width="%s" height="%s" src="%s" allowfullscreen></iframe>',
            $view->escapeHtml($options['width']),
            $view->escapeHtml($options['height']),
            $view->escapeHtml($url)
        );
        return $embed;
    }
}