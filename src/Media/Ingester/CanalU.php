<?php
namespace IngesterS\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Downloader;
use Omeka\Stdlib\ErrorStore;
use Omeka\Media\Ingester\IngesterInterface;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Url as UrlElement;
use Laminas\Uri\Http as HttpUri;
use Laminas\Http\Client;
use Laminas\View\Renderer\PhpRenderer;

class CanalU implements IngesterInterface
{
    /**
     * @var Downloader
     */
    protected $downloader;

    public function __construct(Downloader $downloader)
    {
        $this->downloader = $downloader;
    }

    public function getLabel()
    {
        return 'CanalU'; // @translate
    }

    public function getRenderer()
    {
        return 'CanalU';
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No CanalU URL specified');
            return;
        }
        $uri = new HttpUri($data['o:source']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', 'Invalid CanalU URL specified');
            return;
        }
        // Example CanalU Url: https://www.canal-u.tv/137384 <- most practical
        // Example https://www.canal-u.tv/chaines/ehess/captations/memorial-ou-la-memoire-en-peril-lorsque-le-temps-present-se-heurte-au <- ok its pretty, but extracting an Id from this is hard
        // You can find it from the Notice tab on each video
        switch ($uri->getHost()) {
            // error handling checking for invaid input
            case 1 == 1:
                //extracts the id
                $youtubeId = substr($url, strrpos($url, '/') + 1);
                break;
            default:
                $errorStore->addError('o:source', 'Invalid YouTube URL specified, not a YouTube URL');
                return;
        }

        /*
        // Builds the thumbnail
        // TODO 
        $url = sprintf('http://img.youtube.com/vi/%s/0.jpg', $youtubeId);
        $tempFile = $this->downloader->download($url);
        if ($tempFile) {
            $tempFile->mediaIngestFile($media, $request, $errorStore, false);
        }
        */

        $mediaData = ['id' => $youtubeId];
        /*
        $start = trim($request->getValue('start'));
        if (is_numeric($start)) {
            $mediaData['start'] = $start;
        }
        $end = trim($request->getValue('end'));
        if (is_numeric($end)) {
            $mediaData['end'] = $end;
        }
        */
        $media->setData($mediaData);
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => 'Video URL', // @translate
            'info' => 'URL for the video to embed.', // @translate
        ]);
        $urlInput->setAttributes([
            'id' => 'media-youtube-source-__index__',
            'required' => true,
        ]);
        $startInput = new Text('o:media[__index__][start]');
        $startInput->setOptions([
            'label' => 'Start', // @translate
            'info' => 'Begin playing the video at the given number of seconds from the start of the video.', // @translate
        ]);
        $endInput = new Text('o:media[__index__][end]');
        $endInput->setOptions([
            'label' => 'End', // @translate
            'info' => 'End playing the video at the given number of seconds from the start of the video.', // @translate
        ]);
        return $view->formRow($urlInput)
            . $view->formRow($startInput)
            . $view->formRow($endInput);
    }
}