<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GforceStock extends Model
{
    // -------------------------------------------------------------------------------------------------------------------
    // gForce lookups. See config/fields/gforce.php

    /**
     * Returns the stocks database enum for a gForce body_type key
     *
     * @param string $key The CAP body_type key, i.e. 'City-Car'
     * @return string       The stocks table body_type enum, i.e. 'city_car'
     */
    public static function getBodyType($key)
    {
        return self::lookup('body_type', $key);
    }

    /**
     * Returns the stocks database enum for a gForce body_type key
     *
     * @param string $key The CAP fuel_type key, i.e. 'P'
     * @return string       The stocks table fuel_type enum, i.e. 'petrol'
     */
    public static function getFuelType($key)
    {
        return self::lookup('fuel_type', $key);
    }

    /**
     * Returns the stocks database enum for a gForce transmission key
     *
     * @param string $key The CAP fuel_type key, i.e. 'Automatic'
     * @return string       The stocks table transmission enum, i.e. 'automatic'
     */
    public static function getTransmission($key)
    {
        return self::lookup('transmission', $key);
    }

    /**
     * Returns the stocks database enum for a gForce body_type key
     *
     * @param string $field
     * @param string $key
     * @return string
     * @throws \Exception
     */
    protected static function lookup($field, $key)
    {
        $value = array_get(config("constants.gforce.lookups.$field"), $key);
        if (!$value) {
            throw new \Exception("Could not find CAP key `$key` in config `constants.cap.lookups.$field`");
        }
        return $value;
    }
}
