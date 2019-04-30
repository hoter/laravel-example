<?php

namespace App\Services\Images;

use App\Exceptions\NotImplementedException;
use Illuminate\Contracts\Support\Jsonable;
use Storage;

/**
 * Service to get images from 3rd party services and save them to the filesystem
 *
 * This class is abstract; to create services for other feeds:
 *
 *  - duplicate
 *  - change the `provider` string
 *  - load any provider-specific configuration in the constructor
 *  - override the fetch() method
 *
 *
 * Note that this class does NOT manage models; it is designed to manage media only
 *
 * The following properties are available:
 *
 * @property string  $root      The root path to the public folder
 * @property string  $provider  The provider name, creates subfolders
 * @property int     $id        The id of the image set; should probably be a derivative or stock id
 * @property string  $name      The name of the image; i.e. front, side, etc
 * @property int[]   $size      The size of the image
 * @property string  $path      The full path to the image
 * @property string  $url       The URL to the image
 * @property bool    $exists    A boolean indicating if the image exists on the hard disk
 * @property mixed[] $values    All values of the image in an array
 * @property string  $image     The HTML string for an <img> tag
 *                              
 */
abstract class ImageService
{
    // -----------------------------------------------------------------------------------------------------------------
    // properties
    // -----------------------------------------------------------------------------------------------------------------

    // config
    protected $provider;
    protected $root;

    // sizes
    protected static $sizes;

    // image
    protected $id   = 0;
    protected $name = '';
    protected $path = '';
    protected $size = [];

    protected $cdnUrl;

    // -----------------------------------------------------------------------------------------------------------------
    // instantiation
    // -----------------------------------------------------------------------------------------------------------------

    public function __construct()
    {
        $this->root    = trim(config('constants.images.path'), '/') . '/';
        $this->cdnUrl  = config('constants.images.cdn_url');
        static::$sizes = config('constants.images.sizes');
    }

    /**
     * Static creation method, inherited by subclasses
     *
     * @param int    $id   A derivative id
     * @param string $name An image name
     * @param array  $size An image size
     * @return static
     */
    public static function create($id = null, $name = null, ...$size)
    {
        return (new static)->init($id, $name, ...$size);
    }

    public function init($id, $name, ...$size)
    {
        $this->setId($id);
        $this->setName($name);
        $this->setSize(...$size);
        return $this;
    }


    // -----------------------------------------------------------------------------------------------------------------
    // setters
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Magic getter
     *
     * Returns @properties above, and also any getXXX methods as xxx
     *
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        $getter = 'get' . ucwords($name);
        if (method_exists($this, $getter))
        {
            return $this->$getter();
        }
        if (property_exists($this, $name))
        {
            return $this->$name;
        }
        return null;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // setters
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Set the id of the image
     *
     * This translates into a folder; should be unique per provider
     *
     * @param $id
     * @return ImageService
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this->update();
    }

    /**
     * Set the name of the image; should be unique per folder
     *
     * @param $name
     * @return ImageService
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this->update();
    }

    /**
     * Set the size of the image
     *
     * Pass the following:
     *
     * - the preset values "thumb" or "full"
     * - a pair of numbers to set the exact size
     * - a single number, to scale proportionally to the "full" size preset
     * - true, false or null, to reset to "full" size
     *
     * @param string|number $width  a size preset or a numeric value
     * @param number|null   $height an optional numeric value
     * @return $this
     */
    public function setSize($width = null, $height = null)
    {
        $size = [$width, $height];
        $full = static::$sizes['full'];
        if (is_string($width))
        {
            $size = array_get(static::$sizes, $width);
            if (empty($size))
            {
                $size = $full;
            }
        }
        else if (is_numeric($width))
        {
            if (!$height)
            {
                $ratio = $full[1] / $full[0];
                $size  = [$width, $width * $ratio];
            }

        }
        else if ($width == null || $width == true || $width == false)
        {
            $size = $full;
        }
        $this->size = [(int) $size[0], (int) $size[1]];
        return $this->update();
    }


    // -----------------------------------------------------------------------------------------------------------------
    // getters
    // -----------------------------------------------------------------------------------------------------------------

    public function getUrl()
    {
        return $this->cdnUrl . $this->root. $this->path;
    }

    public function getValues()
    {
        return [
            'id'     => $this->id,
            'name'   => $this->name,
            'path'   => $this->getPath(),
            'url'    => $this->getUrl(),
            'exists' => $this->exists,
            'width'  => $this->size[0],
            'height' => $this->size[1],
            'size'   => $this->size,
        ];
    }

    public function getImage()
    {
        $rand = microtime();
        return "<img src='{$this->url}?{$rand}'>";
    }

    public function getPath()
    {
        return $this->root . $this->path;
    }

    public function getPublicPath()
    {
        return $this->cdnUrl . $this->root. $this->path;
    }

    public function getExists()
    {
        return Storage::disk('s3')->exists($this->root . $this->path);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // methods
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Load the image from the API
     *
     * If the image already exists on the disk, loading is skipped unless $refresh is true
     *
     * @param bool $refresh optionally refresh any existing image
     * @return $this
     */
    public function load($refresh = false)
    {
        if (!$this->getExists() || $refresh)
        {
            $this->fetch();
        }
        return $this;
    }

    /**
     * Shows the image in the browser
     */
    public function show()
    {
        echo $this->getImage();
        return $this;
    }

    /**
     * Save image data
     *
     * Should be called automatically by child classes, but is provided here as a convenience
     *
     * @param $data
     * @return $this
     */
    protected function save($data)
    {
        Storage::disk('s3')->put( $this->root . $this->path, $data);
        return $this;
    }

    /**
     * Delete the image
     *
     * @return bool
     */
    public function delete()
    {
        return Storage::disk('s3')->delete($this->path);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // protected methods
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Main fetch method, should also save the image
     *
     * @abstract
     */
    protected function fetch ()
    {
        throw new NotImplementedException('Override fetch in subclass');
    }

    /**
     * Update the image file's path
     *
     * @return $this
     */
    protected function update()
    {
        $size       = implode('x', $this->size);
        $this->path = "{$this->provider}/{$this->id}/{$this->name}-{$size}.jpg";
        return $this;
    }

}
