<?php

namespace App\Http\Controllers\Sketchpad;

use App\Helpers\JsonPaginator;
use App\Http\Requests\Cars\CarBrowseRequest;
use App\Http\Requests\Cars\CarPriceRequest;
use App\Models\Stock;
use App\Repositories\StockRepo;
use App\Services\Products\PCP;
use App\Services\RatingService;
use App\Services\Stocks\ColourService;
use DB;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StocksController
{

    /**
     * @field select $product   options:PCP=new_car_pcp,HP=car_loan_hp
     * @field select $rating    options:excellent,very_good,good,fair,poor,very_poor
     * @field select $car_type  options:,new,used
     * @field select $fuel      options:,petrol,diesel
     * @field select $body      options:,hatchback,saloon,city_car,4x4,estate,mpv,coupe,sports
     * @field number $deposit   min:0|max:10000|step:1000
     * @field number $term      min:12|max:72|step:6
     * @field number $mileage   min:10|max:200|step:5
     * @field number $min       min:100|max:10000|step:100
     * @field number $max       min:100|max:10000|step:100
     * @field select $sortKey   options:id,monthly_payment,make,age
     * @field select $sortOrder options:ASC,DESC
     * @group Multiple
     */
    public function queryStocks(
        $product = 'new_car_pcp',
        $term = 24,
        $mileage = 30,
        $deposit = 1000,
        $rating = 'good',

        $car_type = '',
        $fuel = 'petrol',
        $body = '',
        $min = 200,
        $max = 400,
        $sortKey = 'id',
        $sortOrder = 'ASC',
        $page = 1)
    {
        $request = new CarBrowseRequest([
            'car'    => [
                'car_type'     => $car_type,
                'fuel_type'    => $fuel,
                'body_type'    => $body,
                'transmission' => '',
                'make'         => '',
                'doors'        => '',
            ],
            'rating' => $rating,
            'loan'   => [
                'product' => $product,
                'deposit' => $deposit,
                'term'    => $term,
                'mileage' => $mileage,
            ],
            'budget' => [
                'min' => $min,
                'max' => $max,
            ],
            'sort'   => [
                'key'   => $sortKey,
                'order' => $sortOrder,
            ],
        ]);
        $data    = StockRepo::browse($request);

        tb($data->results, 'width:1200');
        dd($data->pagination);
    }

    public function getSamples($deposit = 1000,
                               $term = 24,
                               $rating = 'good',
                               $mileage = 30,
                               $budget = 400)
    {
        $data   = [
            'product' => 'new_car_pcp',
            'deposit' => $deposit,
            'term'    => $term,
            'rating'  => $rating,
            'mileage' => $mileage,
            'budget'  => $budget,
        ];
        $client = new Client();
        try
        {
            $response = $client->post('http://192.168.10.10/api/v2/cars/samples', [
                'form_params' => $data,
            ]);
            $results  = json_decode($response->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            return $e->getResponse()->getBody()->getContents();
        }
        tb($results);
    }

    /**
     * @field select $car_type  options:New=new,Used=used
     *
     * @param string $car_type
     * @param string $desc
     */
    public function typeAhead($car_type = 'new', $desc = '')
    {
        tb(StockRepo::filterByName($car_type, $desc));
    }

    /**
     * @field select $car_type  options:Any=,New=new,Used=used
     */
    public function getMakesAndModels($car_type = '', $html = false)
    {
        $makes = collect(StockRepo::getMakes($car_type));
        if ($html)
        {
            $makes->map(function ($make) use ($car_type) {
                echo "<h4>$make</h4><ul>";
                collect($this->getModels($make, $car_type))->map(function ($model) {
                    echo "<li>$model</li>";
                });
                echo "</ul>";
            });
        }

        return $makes
            ->mapWithKeys(function ($make) use ($car_type) {
                return [$make => $this->getModels($make, $car_type)];
            });
    }

    /**
     * @field select $car_type  options:Any=,New=new,Used=used
     */
    public function getMakes($car_type = '')
    {
        return StockRepo::getMakes($car_type);
    }

    /**
     * @field select $car_type  options:Any=,New=new,Used=used
     */
    public function getModels($make = 'Audi', $car_type = '')
    {
        return StockRepo::getModels($make, $car_type);
    }

    public function paginate(Request $request, $page = 1)
    {
        $data    = Stock::all();
        $results = JsonPaginator::create($data, null, 10);

        $paginator = new LengthAwarePaginator($data, count($data), 15, null, ['path' => '']);
        echo $paginator->render();
        tb($results->results);
    }

    /**
     * @group Single
     */
    public function getSingleStock($id = 1)
    {
        $stock = Stock::find($id);
        return $stock
            ->getData();
    }

    /**
     * Get the price of a single car. Note that this call requires the correct `car_type` to be sent, or it will result in 0 results
     *
     * @field select $car_type  options:New=new,Used=used
     * @field select $product   options:PCP=new_car_pcp,HP=car_loan_hp
     * @field select $rating    options:excellent,very_good,good,fair,poor,very_poor
     * @field number $deposit   min:0|max:10000|step:1000
     * @field number $term      min:12|max:72|step:6
     * @field number $mileage   min:10|max:200|step:5
     */
    public function getPrice($id = 1,
                             $car_type = 'new',
                             $product = 'new_car_pcp',
                             $rating = 'good',
                             $deposit = 1000,
                             $term = 24,
                             $mileage = 30,
                             $debug = false)
    {
        $request = new CarPriceRequest([
            'id'       => $id,
            'deposit'  => $deposit,
            'term'     => $term,
            'mileage'  => $mileage,
            'rating'   => $rating,
            'product'  => $product,
            'car_type' => $car_type,
        ]);

        $result = StockRepo::getPrice($request, $debug);
        $debug
            ? pr($result)
            : ls($result);
    }

    /**
     * Works out HP + Balooon payments by offsetting a percentage of the GFV into the loan
     *
     * In this case, the GFV is less, and so the
     *
     * @field    select $rating    options:excellent,very_good,good,fair,poor,very_poor
     * @field    number $term      min:12|max:72|step:6
     *
     * @internal param int $amount
     * @param int    $id
     * @param string $rating
     * @param int    $deposit
     * @param int    $term
     * @param int    $mileage
     * @param int    $percent
     * @return array
     */
    public function getHpBalloonPrice($id = 1,
                                      $rating = 'good',
                                      $deposit = 1000,
                                      $term = 36,
                                      $mileage = 10,
                                      $percent = 20)
    {
        $dp      = (float) (100.0 - $percent) / 100.0;
        $stock   = Stock::find($id);
        $amount  = $stock->customer_price;
        $gfv     = $stock->getGFV($mileage, $term);
        $apr     = app(RatingService::class)->getAprForProduct('new_car_pcp', $rating);
        $product = new PCP($amount - $deposit, $term, $apr, $gfv * $dp);

        return ['stock' => $stock, 'product' => $product->toArray()];
    }

    /**
     * @field number $term      min:12|max:72|step:6
     * @field number $mileage   min:10|max:200|step:5
     */
    public function getHarlibData($id = 1, $term = 24, $mileage = 30)
    {
        return Stock::find($id)->getHarlibData($term, $mileage);
    }

    /**
     * @group colours
     */
    public function getStocksColours()
    {
        $service = app(ColourService::class);
        $colours = DB::table('stocks')
            ->distinct()
            ->pluck('colour')
            ->map(function ($spec) use ($service) {
                $colour = $service->parse($spec);
                return ['colour' => $colour, 'colour_spec' => $spec];
            })
            ->sortBy('colour')
            ->toArray();
        tb(array_values($colours), 'index');
    }

    public function showUnlistedColours()
    {
        $service = app(ColourService::class);
        $colours = DB::table('stocks')
            ->distinct()
            ->pluck('colour')
            ->map(function ($spec) use ($service) {
                $colour = $service->parse($spec);
                return ['colour_spec' => $spec, 'colour' => $colour];
            })
            ->values()
            ->all();
        $colours = array_filter($colours, function ($e) {
            return $e['colour'] === 'other';
        });
        tb(array_values($colours));
    }

    public function testColor($color = 'metallic grey')
    {
        return app(ColourService::class)->parse($color);
    }

    public function colourService()
    {
        vd(app(ColourService::class));
    }

    /**
     * @group Development
     *
     * @param string $type
     * @return mixed
     */
    public function isTransmisson($type = 'manual')
    {
        return Stock::isTransmission($type)->count();
    }

    public function isFuel($type = 'petrol')
    {
        return Stock::isFuelType($type)->count();
    }

    public function hasDoors($doors = 2)
    {
        return Stock::hasDoors($doors)->count();
    }

    /**
     * @field number $term      min:12|max:72|step:6
     * @field number $mileage   min:10|max:200|step:5
     */
    public function getGfv($id = 1, $mileage = 10, $term = 24)
    {
        return Stock::find($id)->getGFV($mileage, $term);
    }

}

