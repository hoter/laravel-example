<?php

namespace App\Models;

use App\Services\Products\Product;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Stock
 *
 * @package App\Models
 */
class Stock extends Model
{
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public $dates = [
        'created_at',
        'updated_at',
        'registration_date',
    ];

    protected $guarded = [];


    // -------------------------------------------------------------------------------------------------------------------
    // relationships
    // -------------------------------------------------------------------------------------------------------------------

    public function gfv()
    {
        return $this->hasMany(GFV::class, 'derivative_id', 'derivative_id');
    }

    public function carModel(){
        return $this->hasOne(CarModel::class, 'model_cap_id', 'model_id');
    }

    public function isBca(){
        return $this->supplier == 'BCA';
    }


    // -------------------------------------------------------------------------------------------------------------------
    // relations
    // -------------------------------------------------------------------------------------------------------------------

    /**
     * Not yet implemented with proper polymorphic relations
     *
     * @see https://stackoverflow.com/questions/26433885/laravel-hasmany-with-where
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(StockImage::class, 'stock_id');
    }

    // -------------------------------------------------------------------------------------------------------------------
    // query scopes
    // -------------------------------------------------------------------------------------------------------------------

    public function scopeIsCarType(Builder $query, $car_type)
    {
        return $query->where('car_type', $car_type);
    }

    public function scopeIsBodyType(Builder $query, $type)
    {
        return $query->where('body_type', $type);
    }

    public function scopeIsFuelType(Builder $query, $value)
    {
        return $query->where('fuel_type', $value);
    }

    public function scopeIsTransmission(Builder $query, $value)
    {
        return $query->where('transmission', $value);
    }

    public function scopeHasDoors(Builder $query, $number)
    {
        return $query->where('doors', $number);
    }


    // -------------------------------------------------------------------------------------------------------------------
    // methods
    // -------------------------------------------------------------------------------------------------------------------

    public function getData()
    {
        $data           = $this->toArray();
        $data['images'] = $this->getImages();
        return $data;
    }

    /**
     * Temp get images function; to be replace by polymorphic relationships when we get time
     *
     * @return array
     */
    public function getImages()
    {
        if($this->is_real_image == 1) {
            return \DB::table('stock_images')
                    ->where(['type' => 'stock', 'related_id' => $this->id])
                    ->get(['path AS url', 'path', 'width', 'height']);
        } else {
            return \DB::table('stock_images')
                    ->where(['type' => 'derivative', 'related_id' => $this->derivative_id])
                    ->get(['path AS url', 'path', 'width', 'height'])
                    ->map(function ($image) {
                            $image->url = url($image->path);
                            return $image;
                    });
        }

    }

    /**
     * Get the GFV for a given annual mileage and term (in months)
     *
     * @param number $annualMileage
     * @param number $term
     * @return number
     */
    public function getGFV($annualMileage, $term)
    {
        $mileage = Product::getTotalMileage($annualMileage, $term);
        $term    = 'term_' . Product::getTerm($term);
        return $this->gfv()
            ->where('mileage', $mileage)
            ->firstOrFail()
            ->$term;
    }

    public function getHarlibData($term, $mileage)
    {
        $term    = Product::getTerm($term);
        $mileage = Product::getTotalMileage($mileage, $term);

        return (object) DB::table('stocks AS s')
            ->select([
                's.thumb_url',
                's.car_type AS type',
                's.make',
                's.model',
                's.colour',
                's.colour_spec',
                's.model_year',
                's.doors',
                's.fuel_type',
                's.body_type',
                's.co2',
                's.supplier',
                's.cap_code',
                's.supplier_spec',
                's.derivative_id',
                's.derivative',
                's.standard_option',
                's.additional_option',
                's.registration_no',
                's.registration_date',
                's.chassis_no',
                's.vin_number',
                's.tax_amount_six_month',
                's.tax_amount_twelve_month',
                's.id as stock_id',
                's.stock_ref',
                's.customer_price',
                's.current_price',
                's.purchase_price',
                's.customer_discount_percentage',
                's.customer_discount_amount',
                's.vat',
                's.current_mileage',
                's.sale_location',
                "g.term_{$term} AS gfv",
            ])
            ->leftJoin('gfv AS g', function($join) use($mileage){
                $join->on('s.derivative_id', '=', 'g.derivative_id')->where('g.mileage', '=', $mileage);
            })
            ->where('s.id', '=', $this->id)
            ->first();
    }

    /**
     * if a stock already exists in database having
     * same supplier, dirivitive_id,colour,standard_option,additional_option
     * then stock_count will be incremented otherwise new stock will be
     * created using $stockData.
     *
     * @param array $stockData
     * @return boolean
     */
    public static function incrementOrInsert(array $stockData)
    {
        //TODO waiting for business logic will it increment stock_count or insert new
//        $stock = Stock::where([
//            ['supplier', '=', $stockData['supplier']],
//            ['derivative_id', '=' , $stockData['derivative_id']],
//            ['colour', '=' ,  $stockData['colour']],
//            ['standard_option', '=' , $stockData['standard_option']],
//            ['additional_option', '=' , $stockData['additional_option']]
//        ])->first();
//
//        if($stock)
//        {
//            $stock->stock_count = $stock->stock_count + 1;
//            return $stock->save();
//        }

        return Stock::create($stockData);
    }
}
