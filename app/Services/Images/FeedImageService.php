<?php

namespace App\Services\Images;

/**
 * Remote Image Service
 *
 * Fetches an image from a URL, saves it, and resizes it
 *
 * Can also fetch local images if required, as it uses file_get_contents()
 *
 * @usage
 *
 *  $service
 *      ->init($stockId, $imageId, $size)
 *      ->loadUrl($url);
 */
class FeedImageService extends ImageService
{
    // -----------------------------------------------------------------------------------------------------------------
    // properties
    // -----------------------------------------------------------------------------------------------------------------

    protected $provider = 'feed';

    protected $remoteUrl;

    protected $tmpPath;

    // -------------------------------------------------------------------------------------------------------------------
    // public methods
    // -------------------------------------------------------------------------------------------------------------------

    /**
     * Load an image from a URL
     *
     * @param string $url     The URL of the image you want to load (this can also be a local image path)
     * @param bool   $refresh optionally refresh any existing image
     */
    public function loadUrl($url, $refresh = false)
    {
        $this->remoteUrl = $url;
        $this->tmpPath = config("constants.images.temp_path");
        $this->load($refresh);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // protected methods
    // -----------------------------------------------------------------------------------------------------------------

    protected function fetch()
    {
        if (!$this->remoteUrl)
        {
            throw new \Exception('Image URL has not been set');
        }

        // load and save image
        /** todo: Dont need to save full image for URL for now. There is some complexity in saving full image and resize.
            need implementing to save URL image later.
         */
        //$data = file_get_contents($this->remoteUrl);
        // $this->save($data);

        // resize
        $this->resize($this->size[0], $this->size[0]);
    }

    protected function resize($w, $h = 0, $crop = FALSE)
    {
        // variables
        $path = $this->remoteUrl;
        list($width, $height) = getimagesize($path);

        // proportionally resize if h not supplied
        if (!$h)
        {
            $h = $w * ($height / $width);
        }

        // calculate
        $r = $width / $height;
        if ($crop)
        {
            if ($width > $height)
            {
                $width = ceil($width - ($width * abs($r - $w / $h)));
            }
            else
            {
                $height = ceil($height - ($height * abs($r - $w / $h)));
            }
            $newWidth  = $w;
            $newHeight = $h;
        }
        else
        {
            if ($w / $h > $r)
            {
                $newWidth  = $h * $r;
                $newHeight = $h;
            }
            else
            {
                $newHeight = $w / $r;
                $newWidth  = $w;
            }
        }

        // resize
        $src = imagecreatefromjpeg($path);
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // saving content to temporary storage
        imagejpeg($dst, base_path() . $this->tmpPath . $this->name . ".jpeg");

        // saving content to s3
        $content = file_get_contents(base_path() . $this->tmpPath . $this->name . ".jpeg");
        $this->save($content);

        //removing temporary file
        unlink(base_path() . $this->tmpPath . $this->name . ".jpeg");

    }

}