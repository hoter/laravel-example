<?php

namespace App\Helpers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * JsonResponse
 *
 * Custom class to manage wrap response data.
 *
 * Note:
 *
 * - class can be returned from controllers, as it serializes automatically
 * - original data is stored in array format
 *
 * @usage
 *
 * Get one value:
 *
 * - $foo     = $response->foo;
 * - $foo     = $response->get('foo');
 * - $foobar  = $response->get('foo.bar');
 *
 * Get some values
 *
 * - $values  = $response->get(['foo', 'bar']);
 * - $assoc   = $response->only('foo', 'bar');
 * - $assoc   = $response->only(['foo', 'bar']);
 *
 * Get all values:
 *
 * - $assoc   = $response->all();
 * - $object  = $response->all(true);
 *
 * Transform to another class:
 *
 * - $foo     = $response->as(Custom::class)->foo
 * - $random  = $response->as(\Illuminate\Support\Collection::class)->random();
 *
 */
class JsonHelper implements Jsonable, Arrayable
{

    // -----------------------------------------------------------------------------------------------------------------
    // properties
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @var array $data
     */
    protected $data;


    // -----------------------------------------------------------------------------------------------------------------
    // instantiation
    // -----------------------------------------------------------------------------------------------------------------

    public function __construct($data)
    {
        $this->data = is_string($data)
            ? json_decode($data, true)
            : $data;
    }

    public static function create($data)
    {
        $data = is_string($data)
            ? json_decode($data, true)
            : $data;
        return new static($data);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // accessors
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Access properties directly
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // public methods
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Get one or many data values
     *
     * @param   string|array $path
     * @return  mixed
     */
    public function get($path)
    {
        if (is_array($path))
        {
            return array_map(function ($key) {
                return $this->get($key);
            }, $path);
        }
        return array_get($this->data, $path);
    }

    /**
     * Get only some keys from the response
     *
     * @param array $keys Pass an array of key names or variadic parameters
     * @return array
     */
    public function only($keys)
    {
        $keys = is_array($keys)
            ? $keys
            : func_get_args();
        return array_only($this->data, $keys);
    }

    /**
     * Get all of the data as
     *
     * @param bool $object
     * @return mixed
     */
    public function all($object = false)
    {
        return $object
            ? json_decode(json_encode($this->data))
            : $this->data;
    }

    /**
     * Return the response as another class
     *
     * The instantiated class should take the response data as its first constructor parameter
     *
     * @param string $class      The class to instantiate
     * @param array  $parameters Any additional parameters to pass
     * @return object
     */
    public function as($class, ...$parameters)
    {
        return new $class($this->data, ...$parameters);
    }


    // -----------------------------------------------------------------------------------------------------------------
    // utilities
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->data);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}
