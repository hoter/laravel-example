<?php

class Image
{

    // -------------------------------------------------------------------------------------------------------------------
    // properties

    protected $path;


    // -------------------------------------------------------------------------------------------------------------------
    // instantiation

    public function __construct($path = null)
    {
        if ($path) {
            $this->load($path);
        }
    }

    public static function create($path = null)
    {
        return new self($path);
    }

    // -------------------------------------------------------------------------------------------------------------------
    // public methods

    public function load($path)
    {
        if (strpos($path, 'http') === 0) {
            $this->path = $this->fetch($path);
        }
        else {
            $this->path = $path;
        }
        if (!file_exists($path)) {
            throw new Exception("Image file '$path' not found");
        }
        return $this;
    }

    public function resize($width, $height = null)
    {
        $this->resizeImage($width, $height);
        return $this;
    }

    public function save($path)
    {
        if ($path !== $this->path) {
            rename($this->path, $path);
            $this->path = $path;
        }
        return $this;
    }

    public function url ()
    {
        
    }


    // -------------------------------------------------------------------------------------------------------------------
    // protected functions

    protected function fetch($url, $path = null)
    {
        if (!$path) {
            $path = $this->tempFile();
        }
        file_put_contents($path, file_get_contents($url));
        return $path;
    }

    protected function tempFile () {
        return tempnam(sys_get_temp_dir(), 'image_');
    }

    protected function resizeImage($path, $w, $h = null, $crop = FALSE)
    {
        // variables
        list($width, $height) = getimagesize($path);

        // proportionally resize if h not supplied
        if (!$h) {
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

        return $dst;
    }
}
