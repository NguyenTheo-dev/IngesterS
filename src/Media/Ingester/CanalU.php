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
use DOMDocument;

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
        return 'canalu';
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
        //only checks if we provided a url containing an id, and from the correct host
        switch ($uri->getHost()) {
            // error handling checking for invaid input
            case "www.canal-u.tv":

                // Extract the path portion of the URL (i.e. everything after the hostname)
                $path = parse_url($uri, PHP_URL_PATH);

                // Split the path into its component parts
                $parts = explode('/', trim($path, '/'));

                //extracts the id
                $youtubeId = $parts[count($parts) - 1];
                //if the id isn't empty, and that removing every numbers leaves nothing then we have a valid id
                // maaaaaybe is_numeric would work as well
                if( $youtubeId !== '' && trim($youtubeId, ' 1234567890') === '' ){
                    break;
                }
                else{
                    $errorStore->addError('o:source', 'Invalid Canal-U URL specified, id is not only numbers');
                    return;
                }
            default:
                $errorStore->addError('o:source', 'Invalid Canal-U URL specified, wrong host');
                return;
        }

        // Builds the thumbnail
        // Also get some useful data for the embedding
        $cURLConnection = curl_init();
        
        //we get the non-perma link, because it's where we can find the thumbnail as well as the embed info
        // TODO (maybe) : Laminas-ize the web queries
        curl_setopt($cURLConnection, CURLOPT_URL, $uri);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($cURLConnection);
        $dom = new DOMDocument();
        $dom->loadHTML($response, LIBXML_NOERROR);
        $redirect = $dom->getElementsByTagName('a');
        $newUrl = $redirect->item(0)->nodeValue;

        //we get the permalink url from the previous result
        if (!isset($newUrl)) {

            // The URL parameter was not found in the HTML content
            $errorStore->addError('o:source', 'Permalink could not be extracted from the URL specified');
        }

        $canonical;
        $thumbLink;
        
        if (isset($newUrl)){

            curl_setopt($cURLConnection, CURLOPT_URL, $newUrl);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($cURLConnection);
            $dom = new DOMDocument();
            $dom->loadHTML($response, LIBXML_NOERROR);
            $links = $dom->getElementsByTagName('link');
            // normally, we only care about the first two links
            $canonical = $links->item(0)->getAttribute('href');
            $thumbLink = $links->item(1)->getAttribute('href');
        }

        curl_close($cURLConnection);

        if(isset($thumbLink)){

            $url = $thumbLink;
            $tempFile = $this->downloader->download($url);
            if ($tempFile) {
                $tempFile->mediaIngestFile($media, $request, $errorStore, false);
            }
        }

        if(isset($canonical)){

            $mediaData['canonical'] = $canonical;

            $path = parse_url($canonical, PHP_URL_PATH);
            // Split the path into its component parts
            $parts = explode('/', trim($path, '/'));
            $mediaData['channel'] = $parts[1];

        }

        $mediaData['id'] = $youtubeId;
        
        $start = trim($request->getValue('start'));
        if (is_numeric($start)) {
            $mediaData['start'] = $start;
        }
        
        $media->setData($mediaData);
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => 'Video URL', // @translate
            'info' => 'URL for the video to embed. Please use the permalink, which ends in a numeric Id.', // @translate
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
        return $view->formRow($urlInput)
            . $view->formRow($startInput);
    }
}