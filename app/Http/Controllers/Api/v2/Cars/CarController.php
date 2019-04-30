<?php

namespace App\Http\Controllers\Api\v2\Cars;

use App\Http\Controllers\Api\v2\ApiController;
use App\Http\Requests\Cars\CarBrowseRequest;
use App\Http\Requests\Cars\CarPriceRequest;
use App\Http\Requests\Cars\CarSearchRequest;
use App\Http\Requests\Cars\CarViewRequest;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Repositories\StockRepo;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery\Exception;

class CarController extends ApiController
{
    /**
     * Search and filter cars; return count
     *
     * @param CarSearchRequest $request
     *
     * @return JsonResponse
     */
    public function search(CarSearchRequest $request)
    {
        return (array) StockRepo::filter($request, true);
    }

    /**
     * Get basic and price data for many cars, paginated
     *
     * @param CarBrowseRequest $request
     *
     * @return array
     */
    public function browse(CarBrowseRequest $request)
    {
        return StockRepo::browse($request);
    }

    /**
     * Get basic and price data for many cars
     *
     * @param CarBrowseRequest $request
     *
     * @return array
     */
    public function getCars(CarBrowseRequest $request)
    {
        return [
            'results' => StockRepo::filterByLoan($request),
        ];
    }

    /**
     * Get basic and price data for a three car selection
     *
     * @param Request $request
     *
     * @return array
     */
    public function getCarSamples(Request $request)
    {
        $loan  = $request->only('deposit', 'term', 'mileage');
        $extra = [
            'product' => 'new_car_pcp',
        ];
        $data  = [
            'loan'   => $extra + $loan,
            'rating' => 'good',
            'budget' => [
                'min' => $request->budget - 100,
                'max' => $request->budget,
            ],
        ];

        $results = StockRepo::filterByLoan(new CarBrowseRequest($data));
        return collect($results)
            ->unique('model')
            ->random(3);
    }

    /**
     * Get basic data for a single car
     *
     * @param CarViewRequest $request
     * @param                $id
     *
     * @return array
     */
    public function getCar(CarViewRequest $request, $id)
    {
        $stock = StockRepo::find($id);
        if(!empty($stock)) {
            $data = $stock->getData();
            $data["is_bca"] = $data["supplier"] == 'BCA';
            $data["make"] = config("fields.stocks.makes")[$data["make"]] ?? $data["make"];
            if ($data["car_type"] == 'used') {
                if (trim($data["standard_option"]) != '') {
                    $data["standard_option"] = $this->filterStandardOptions($data["standard_option"]);
                }
            }
            return $data;
        } else {
            return $this->send("Sorry the stock item you are looking for is not available anymore. Please search other sotck item in browse car section");
        }

    }

    private function filterStandardOptions($options){
        $exclueAny = array(
            "VAT Qualifying",
            "Prices Checked",
            "part exchange",
            "finance",
            "0115 855 3076",
            "0115 855 3060"
            );
        $removeText = array(
            "THE HENDY GROUP ARE PROUD TO OFFER THIS STUNNING 1 OWNER KIA SOUL WHICH WAS ORIGINALLY SUPPLIED BY US NEW.",
            "The Hendy Group are proud to offer this stunning ex demo Sorento. This qualifies for our Hendy Approved offering with free winter checks for life - 12mth breakdown cover - main dealer comprehensive inspection.",
            "THE HENDY GROUP ARE PROUD TO OFFER THIS ",
            "THE HENDY GROUP ARE PROUD TO OFFER "
        );
        $options = str_ireplace(", ,", ",", $options);
        $options = explode(",", $options);
        $optionsNew = array();
        if(!empty($options)) {
            foreach($options as $option) {
                $option = trim($option);
                $allOk = false;
                if(strpos($option, "£") === false && !is_numeric($option) && !is_float($option)
                    && strpos($option, "* ") !== 0) {
                    $allOk = true;
                }

                foreach($exclueAny as $exclude) {
                    if(strpos($option, $exclude) !== false) {
                        $allOk = false;
                        break;
                    }
                }
                foreach($removeText as $text) {
                    $option = str_ireplace($text, "", $option);
                }

                $option = trim($option);

                if($allOk) {
                    $optionsNew[] = $option;
                }

            }
        }
        return implode(", ", $optionsNew);

    }
    /**
     * Get price data for a single car
     *
     * @param CarPriceRequest $request
     * @return array
     */
    public function getPrice(CarPriceRequest $request)
    {
        return StockRepo::getPrice($request);
    }

    /**
     * Gets the availability status of a car
     *
     * @param int $id
     * @return bool
     */
    public function getStatus($id)
    {
        return StockRepo::find($id)->status == 1;
    }

    /**
     * Gets available makes
     *
     * @return array
     */
    public function getMakes()
    {
        return StockRepo::getMakes();
    }

    /**
     * Gets available models
     *
     * @param int $make
     * @return array
     */
    public function getModels($make)
    {
        return StockRepo::getModels($make);
    }

    /**
     * Get makes and models by name
     *
     * Used in front end type-ahead
     *
     * @param string $name
     * @return array
     */
    public function getByName(Request $request, $car_type, $name)
    {
        $field = $request->get('node');
        $data  = StockRepo::filterByName($car_type, $name);
        return $field
            ? [$field => $data]
            : $data;
    }

    public function getImage()
    {

        $client = new Client();
        try
        {
            $response = $client->request('GET', $_GET['url']);
            $ext      = $response->getHeader('Content-Type');
            echo '<img src="data:' . $ext[0] . ';base64,' . base64_encode($response->getBody()->getContents()) . '">';
        }
        catch (\Exception $e)
        {
            return $this->send("Image Not Found Or Link Broken, please try again");
        }

    }
	
	public function getSimilarStock($stockId){
		
		$stock = Stock::find($stockId);

		if(!$stock){
			$stock = StockHistory::find( $stockId );
		}
		
		if(!$stock){
			return [];
		}

		if ( $stock ) {
			$similarStocks = Stock::where( [
				'model_id' => $stock->model_id,
				'colour'   => $stock->colour,
				'car_type' => $stock->car_type
			] )->limit( 20 )->get();

			return $similarStocks;

		}

	}
}
