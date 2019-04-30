<?php

namespace App\Repositories;

use App\Helpers\JsonPaginator;
use App\Helpers\LogHelper;
use App\Helpers\SqlHelper;
use App\Http\Requests\Cars\CarBrowseRequest;
use App\Http\Requests\Cars\CarPriceRequest;
use App\Http\Requests\Cars\CarSearchRequest;
use App\Models\Stock;
use App\Models\StockImage;
use App\Services\Harlib\HarlibApi;
use App\Services\Images\CapImageService;
use App\Services\Images\FeedImageService;
use App\Services\Products\Loan;
use App\Services\Products\PCP;
use App\Services\Products\Product;
use App\Services\RatingService;
use App\Services\FinancialService;
use Illuminate\Support\Facades\DB;

class StockRepo
{
    protected static $fields = [
        's.id',
        's.derivative_id',
        's.make',
        's.model',
        's.derivative',
        's.is_real_image',

        's.car_type',
        's.body_type',
        's.fuel_type',
        's.transmission',
        's.doors',
        'IF(supplier = \'BCA\', true, false) as is_bca',

        's.mpg',
        's.bhp',
        's.current_mileage',
        's.model_year',
        's.colour',
        's.colour_spec',

        's.current_price',
        's.customer_price',
        's.customer_discount_amount',
        's.customer_discount_percentage',

        's.supplier',
        //'s.standard_option',
        's.thumb_url',
    ];

    protected static $debugFields = [
        's.id',
        's.derivative_id',
        's.derivative',
        's.current_price',
        's.customer_price',
        's.customer_discount_amount',
    ];

    protected static $filters = [
        's.status = 1',
    ];


    public static function getFinanceCalculation($inputs){
        $api    = app(HarlibApi::class);
        return $api->post('financial-calculation', $inputs)->all();
    }

    public static function find($id)
    {
        $data = Stock::find($id);
        if ($data && isset($data->carModel))
        {
            $data->description = $data->carModel->description;
            unset($data->carModel);
        }
        return $data;
    }

    public static function browse(CarBrowseRequest $request)
    {
        $results = static::filterByLoan($request);
        //Cache::put('', $results, 1);
        return JsonPaginator::create($results);
    }

    /**
     * Query and filter stocks, returning values based on loan parameters
     *
     * @param CarBrowseRequest $request
     * @param bool             $debug
     * @return array|string
     */
    public static function filterByLoan(CarBrowseRequest $request, $debug = false)
    {
        // -------------------------------------------------------------------------------------------------------------
        // parameters
        // -------------------------------------------------------------------------------------------------------------

        $car    = $request->get('car', []);

        if(!empty($car["make"])) {
            $car["make"] = filter_var($car["make"], FILTER_SANITIZE_STRING);
        }
        if(!empty($car["model"])) {
            $car["model"] = filter_var($car["model"], FILTER_SANITIZE_STRING);
        }
        $budget = $request->get('budget', []);



        // -------------------------------------------------------------------------------------------------------------
        // loan
        // -------------------------------------------------------------------------------------------------------------

        // product; hp or pcp
        $product = $request->pluck('loan.product', 'new_car_pcp');

        // deposit in £
        $deposit = $request->pluck('loan.deposit');

        // term in months
        $term = Product::getTerm($request->pluck('loan.term', true));

        // APR in %
        $rating = $request->pluck('rating', true);

        $flat_rate = app(RatingService::class)->getFlatRateForProduct($product, $rating);
        
        // mileage in 10s of 1000s of miles, i.e. 10 = 10,000
        $annualMileage = $request->pluck('loan.mileage', true);
        $mileage       = Product::getTotalMileage($annualMileage, $term);

        $financeInput = [
            'dummy' => false,
            'operation' => 'FLAT_RATE_TO_APR',
            'car_price' => 122001 ?? 0,
            'deposit' => 200,
            'term' => $term,
            'gfv' => 0,
            'apr' => 0,
            'flat_rate' => $flat_rate,
            'interest_rate' => 0,
        ];
        $apr = self::getFinanceCalculation($financeInput);

        // -------------------------------------------------------------------------------------------------------------
        // budget
        // -------------------------------------------------------------------------------------------------------------

        $minLoanAmount = config("constants.pricing.min_loan_amount");
        $minCarPrice = $minLoanAmount + $deposit;
        $priceFilters = [
            'total_repayable > 0',
            "customer_price > {$minCarPrice}"
        ];

        if (!empty($budget))
        {
            $min = $budget['min'];
            $max = $budget['max'];
            if (!empty($min))
            {
                array_push($priceFilters, "monthly_payment >= $min");
            }
            if (!empty($max))
            {
                array_push($priceFilters, "monthly_payment <= $max");
            }
        }

        // -------------------------------------------------------------------------------------------------------------
        // pricing sql
        // -------------------------------------------------------------------------------------------------------------

        // joins
        $fields  = [];
        $filters = [];
        $join    = "";

        // product
        switch ($product)
        {

            /* Financials need to be adjusted for PCP same as HP*/
            case 'new_car_pcp':
                list($ratio, $theta) = PCP::getRatios($term, $apr);
                $gTerm  = "g.term_$term";

                $fields = [
                    "@gfv := $gTerm AS gfv",
                    "@loan := (s.customer_price - $deposit - @gfv) AS loan",
                    "@ratioLoan := @loan * $ratio AS ratioLoan",
                    "@ratioGfv := @gfv * $ratio AS ratioGfv",
                    "ROUND(@payment * $term, 2) AS total_repayable",
                    "ROUND(@payment := (@ratioLoan / $theta) + @ratioGfv, 2) AS monthly_payment",
                ];
                $join   = "LEFT OUTER JOIN\n   gfv g ON s.derivative_id = g.derivative_id";
                array_push($filters, "g.mileage = $mileage");
                array_push($filters, "s.car_type = 'new'");
                break;

            case 'car_loan_hp':
                //"ROUND(@repayable := @loan * $ratio, 2) AS total_repayable",
                //"ROUND(@repayable / $term, 2) AS monthly_payment",

                $ratio  = Loan::getRatio($term, 12);
                $fields = [
                    "@loan := (s.customer_price - $deposit) AS loan",
                    "$apr AS apr",
                    "$deposit AS deposit",
                    "$flat_rate AS flat_rate",
                    "ROUND(@total_interest:= @loan * ($flat_rate/100) * $term/12, 2) AS total_interest",
                    "ROUND(@monthly_payment:= (@loan + @total_interest)/$term, 2) AS monthly_payment",
                    "ROUND(@repayable := @monthly_payment * $term, 2) AS total_repayable",
                    "@repayable - @loan AS cost_of_credit",
                ];
                break;
        }


        // -------------------------------------------------------------------------------------------------------------
        // car
        // -------------------------------------------------------------------------------------------------------------

        // age: > 3, < 5, etc...
        $age  = array_get($car, 'age', null);
        $year = date("Y");
        if ($age)
        {
            array_push($filters, "$year - s.model_year $age");
            unset($car['age']);
        }
        array_push($fields, "$year - s.model_year as age");

        // mileage
        $mileage = array_get($car, 'mileage', null);
        if ($mileage)
        {
            $car['current_mileage'] = $mileage;
            unset($car['mileage']);
        }


        // -------------------------------------------------------------------------------------------------------------
        // final filters and query
        // -------------------------------------------------------------------------------------------------------------

        $fields       = SqlHelper::select($debug ? self::$debugFields : self::$fields, $fields);
        $stockFilters = SqlHelper::where(self::$filters, $car, $filters);
        $priceFilters = SqlHelper::where($priceFilters);
        $order        = SqlHelper::orderBy($request->sort);

        // print_r($stockFilters); exit;

        // sql
        $statement = @input;
        $sql = "
SELECT * FROM
(SELECT
$fields
FROM
    stocks s
$join
WHERE
$stockFilters
) AS results
WHERE
$priceFilters
$order";

        // results
        if ($debug && function_exists('pr'))
        {
            pr($sql);
        };
        $dbResult = DB::select(DB::raw($sql));

        /*
         * TODO
         * After external API fixed whe have
         * need to implement logic for every element
         */

       foreach ($dbResult as $item)
        {
            $item->make = str_replace("mercedesbenz", 'Mercedes-Benz', $item->make);
            $item->make = str_replace("alfaromeo", 'Alfa Romeo', $item->make);
            
       }
        return $dbResult;
    }

    /**
     * Query and filter stocks, based on car parameters only
     *
     * @param CarSearchRequest $request The request object
     * @param bool             $count   Return the count only
     * @return array
     */
    public static function filter(CarSearchRequest $request, $count = false)
    {
        // parameters
        $fields = SqlHelper::select(self::$fields);
        $order  = $request->get('order', 'id');

        // sql
        $filters = SqlHelper::filters(self::$filters, $request->car);
        $filters = SqlHelper::where($filters);

        // return
        if ($count)
        {
            return DB::select("SELECT count(id) as total from stocks s WHERE $filters")[0];
        }
        return DB::select("SELECT $fields from stocks s WHERE $filters ORDER BY $order");
    }

    /**
     * Get price data for a single car
     *
     * @param CarPriceRequest $request
     * @param bool            $debug
     * @return array
     * @throws \Exception
     */
    public static function getPrice(CarPriceRequest $request, $debug = false)
    {
        // FIXME currently, GFV is not stored for used cars, so we need a different SQL lookup and pro

        // parameters
        $id       = $request->id;
        $product  = $request->product;
        $car_type = $request->car_type;

        // loan parameters
        $deposit = $request->deposit;
        $rate    = static::getRate($product, $request->rating);
        $term    = Product::getTerm($request->term);
        $mileage = Product::getTotalMileage($request->mileage, $term);

        // sql
        $query = DB::table('stocks AS s')
            ->where('s.id', '=', $id);

        $query = $car_type === 'new' && $product != 'car_loan_hp'
            ? $query
                ->select([
                    'current_price',
                    'customer_price',
                    'customer_discount_percentage',
                    'customer_discount_amount',
                    "g.term_{$term} AS gfv",
                ])
                ->leftJoin('gfv AS g', 's.derivative_id', '=', 'g.derivative_id')
                ->where('g.mileage', '=', $mileage)
            : $query
                ->select([
                    'current_price',
                    'customer_price',
                ]);

        $car = $query->first();

        // handle null result
        if (!$car)
        {
            throw new \Exception('Pricing request resulted in no results; check parameters');
        }

        // values
        $car    = (object) $car;
//        $amount = $car->customer_price - $deposit;
//        $gfv    = property_exists($car, 'gfv') ? $car->gfv : 0;
//        $values = Product::create($product, $amount, $term, $rate, $gfv);

        $gfv = $car->gfv ?? 0;

        $financeInput = [
                   'dummy' => false,
                   'operation' => 'FLAT_RATE_TO_APR',
                   'car_price' => $car->customer_price ?? 0,
                   'deposit' => $deposit,
                   'term' => $term,
                   'gfv' => $gfv,
                   'apr' => 0,
                   'flat_rate' => $rate,
                   'interest_rate' => 0,
            ];
        $apr = self::getFinanceCalculation($financeInput);

        $financials = FinancialService::getFinancials($car->customer_price, $deposit, $term, $rate, $gfv);

        $prices = [
            'product'         => $product,
            'monthly_payment' => (double)$financials["monthlyPayment"],
            'total_repayable' => $financials["totalRepayable"],
            'cost_of_credit' => $financials["totalCostOfCredit"],
            'gfv'             => round($gfv, 2),
            'apr'             => $apr,
            'flat_rate'       => $rate
        ];

        // final data
        if ($debug)
        {
            return [
                'car'    => $car,
                'sanity' => [$amount, $gfv, $term, $rate, $values],
                'input'  => $request->all(),
                'loan'   => [
                    'deposit'     => $deposit,
                    'loan_amount' => $amount,
                    'repayable'   => round($values->total_repayable, 2),
                    'payment'     => round($values->monthly_payment, 2),
                    'rate'        => round($rate, 2),
                ],
                'prices' => $prices,
            ];
        }

        return $prices;
    }

    public static function getMakesAndModels($car_type = null)
    {
        // FIXME need to fetch new makes and models from makes table
        $makes = collect(static::getMakes($car_type));
        return $makes
            ->mapWithKeys(function ($make) use ($car_type) {
                return [$make => static::getModels($make, $car_type)];
            });
    }

    public static function getMakes($car_type = null)
    {
        $query = DB::table('stocks');
        if ($car_type)
        {
            $query->where('car_type', $car_type);
        }
        return $query
            ->distinct()
            ->orderBy('make', 'ASC')
            ->pluck('make')
            ->filter(function ($make) {
                return !!$make;
            })
            ->values()
            ->toArray();
    }

    public static function getModels($make, $car_type = null)
    {
        $query = DB::table('stocks');
        if ($car_type)
        {
            $query->where('car_type', $car_type);
        }
        return $query
            ->where('make', $make)
            ->distinct()
            ->orderBy('model', 'ASC')
            ->pluck('model')
            ->toArray();
    }

    public static function filterByName($car_type, $name)
    {
        $name = filter_var($name,  FILTER_SANITIZE_STRING);
        $car_type = filter_var($car_type,  FILTER_SANITIZE_STRING);
        self::checkValidCarType($car_type);

        $name = str_replace(' ', '%', trim($name));
        return $name
            ? DB::select("SELECT DISTINCT make, model, name FROM (SELECT make, model, CONCAT(make, ' ', model) AS name, car_type FROM stocks) s WHERE name LIKE '%$name%' AND car_type = '$car_type' ORDER BY `name`")
            : [];
    }

    public static function filterByDerivative($name)
    {
        $name = filter_var($name,  FILTER_SANITIZE_STRING);
        $name = str_replace(' ', '%', trim($name));
        return $name
            ? DB::select("select DISTINCT * FROM (select id, derivative_id, make, model, CONCAT(make, ' ', model) as name from stocks) s WHERE name LIKE '%$name%'")
            : [];
    }

    protected static function getRate($product, $rating)
    {
        $rate = app(RatingService::class)->getFlatRateForProduct($product, $rating);
        if ($rate === -1)
        {
            throw new \Exception("Invalid rating `$rating`");
        }
        return $rate;
    }


    public static function addStockImages(Stock $objStock, $images)
    {
        if (is_array($images))
        {
            /* saving full size images*/
            foreach ($images as $imageURL)
            {
                self::addStockImage($objStock->id, 'stock', $imageURL);
            }

            /* Saving thumb image */
            $service = FeedImageService::create();
            $service->init($objStock->id, $objStock->id, 'thumb');
            $service->loadUrl(reset($images), true);
            $objStock->thumb_url     = $service->getPublicPath();
            $objStock->is_real_image = 1;
            $objStock->save();

        }
        else
        {
            try
            {
                $service = CAPImageService::create();

                /*saving thumb image*/
                $service->init($objStock->derivative_id, 3, 'thumb')->load();
                $objStock->thumb_url     = $service->getPublicPath();
                $objStock->is_real_image = 0;
                $objStock->save();

                // saving full size images
                $service->setSize('full');
                $path = $service->setName(3)->load()->getUrl();
                self::addStockImage($objStock->derivative_id, 'derivative', $path);
                $path = $service->setName(1)->load()->getUrl();
                self::addStockImage($objStock->derivative_id, 'derivative', $path);
                $path = $service->setName(2)->load()->getUrl();
                self::addStockImage($objStock->derivative_id, 'derivative', $path);
                $path = $service->setName(4)->load()->getUrl();
                self::addStockImage($objStock->derivative_id, 'derivative', $path);
                $path = $service->setName(5)->load()->getUrl();
                self::addStockImage($objStock->derivative_id, 'derivative', $path);
                $path = $service->setName(6)->load()->getUrl();
                self::addStockImage($objStock->derivative_id, 'derivative', $path);
            }
            catch (\Exception $e)
            {
                LogHelper::save('ERROR', new \Exception($e->getMessage() . "Could not get cap image:" .
                    json_encode([$objStock->derivative_id], JSON_PRETTY_PRINT)));

            }
        }
    }

    private static function checkValidCarType($car_type) {
        if(array_key_exists($car_type, config("fields.stocks.car_type")) === false)
            throw new \Exception("Invalid car type `$car_type`");

    }

    public static function addStockImage($related_id, $type, $path)
    {

        $stoc_image = [
            'path'       => $path,
            'size'       => 'full',
            'type'       => $type,
            'related_id' => $related_id,
        ];
        StockImage::insert($stoc_image);
    }

}
