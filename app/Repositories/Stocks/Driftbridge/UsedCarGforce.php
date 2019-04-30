<?php
namespace App\Repositories\Stocks\Driftbridge;

use App\Helpers\LogHelper;
use App\Models\Cap\CAPDer;
use App\Models\Cap\NVDTechnical;
use App\Models\GforceStock;
use App\Repositories\StockRepo;
use App\Repositories\Stocks\AbstractRepository;
use App\Models\Stock;
use App\Services\Stocks\ColourService;
use App\Services\Stocks\PricingService;
use App\Services\Stocks\TaxCalculationService;
use Carbon\Carbon;
use GuzzleHttp\Client;

class UsedCarGforce extends AbstractRepository
{

    public function findCderAndFormatStockArrGforce($data)
    {
        $cderId  = $data['derivative_id'];
        $capder   =  CAPDer::with(['range', 'make', 'NVDTechnical','NVDPrices'])->find($cderId);
        if(!$capder) return null;

        $priceService = app(PricingService::class);
        $taxService = app(TaxCalculationService::class);
        $capPrice = $capder->NVDPrices->first();

        try{
            $price    = $priceService->getUsedCarNonBCAPrice($data['price']);
        }
        catch (\Exception $e)
        {
            LogHelper::save('ERROR', new \Exception($e->getMessage() ."Excel data:".json_encode($this->excelRow, JSON_PRETTY_PRINT)));
            return null;
        }

        if(!$this->isValidPrice($price)) {
            LogHelper::save('ERROR', new \Exception("Price problem :" . json_encode([$this->excelRow, $price], JSON_PRETTY_PRINT)));
            return null;
        }

        $stock                  = new \stdClass();
        $stock->stock_ref       = $data['stock_ref'];
        $stock->chassis_no      = null;
        $stock->vin_number      = empty($data['vin_number']) ? null : $data['vin_number'];
        $stock->car_type        = $data['car_type'];
        $stock->make            = AbstractRepository::sFormatMake($data['make']);
        $stock->model_id        = $capder->range->cran_code;
        $stock->model           = $data['model'];
        $stock->derivative_id   = $capder->cder_ID;
        $stock->cap_code        = trim($capder->cder_capcode);
        $stock->derivative      = trim($capder->cder_name);
        $stock->supplier_spec   = trim($capder->cder_name);
        $stock->fuel_type       = GforceStock::getFuelType(trim($data['fuel_type']));
        $stock->body_type       = $capder->isConvertible() ? 'convertible' : CapDer::getBodyType(trim($capder->capmod->bodyStyle->bs_description)); //GforceStock::getBodyType(trim($data['body_type']))  ;
        $stock->transmission    = GforceStock::getTransmission(trim($data['transmission']));
        $stock->doors           = $data['doors'];
        $stock->colour_spec     = $data['colours'];

        $colourService          = app(ColourService::class);
        $stock->colour          = $colourService->parse($data['colours']);

        if(strlen(trim($data['description'])) <= 10) {
            $optionData = $this->getTechnicalDataAsString($capder->cder_ID);
        } else {
            $optionData = trim($data['description'], ' ,');
        }

        $stock->standard_option = $optionData;

        $stock->additional_option = null;
        $stock->additional_option_price = null;
        $stock->registration_no     = $data['registration_no'];
        $stock->registration_date   = $data['registration_date'];
        $stock->model_year      = $data['model_year'];
        $stock->mpg             = $this->getTechnicalData($capder->NVDTechnical, 'MPG');
        $stock->bhp             = $this->getTechnicalData($capder->NVDTechnical, 'BPH');
        $stock->co2             = $this->getTechnicalData($capder->NVDTechnical, 'CO2');
        $stock->engine_size     = $this->getTechnicalData($capder->NVDTechnical, 'ENGINE_SIZE');

        $stock->current_mileage = $data['current_mileage'];
        $stock->grade           = null;

        $stock->sale_location   = $data['sale_location'];
        $stock->cap_price       = $capPrice->PR_Basic;
        $stock->vat             = $capPrice->PR_Vat;
        $stock->delivery        = $capPrice->PR_Delivery;

        $tax = $taxService->getTaxAmount( $stock->co2, $stock->fuel_type,  $this->getRegistrationDate($stock->registration_date), true);
        $stock->tax_amount_six_month   = $tax['6_months_tax'] ?? null;
        $stock->tax_amount_twelve_month   = $tax['12_months_tax'] ?? null;
        $payableTax = $stock->tax_amount_six_month > 0 ?  $stock->tax_amount_six_month : $stock->tax_amount_twelve_month;

        if(in_array($data['supplier'],config("constants.gforce.add_road_tax_supplier"))) {
            $stock->customer_price = $price['customer_price'] + $payableTax;
            $stock->added_road_tax_amount  = $payableTax;
        } else {
            $stock->customer_price = $price['customer_price'] ;
        }
        $stock->customer_discount_percentage    = $price['customer_discount_percentage'] ?? null;
        $stock->customer_discount_amount        = $price['customer_discount_amount'] ?? null;
        $stock->purchase_discount_percentage    = $price['purchase_discount_percentage'] ?? null;
        $stock->purchase_discount_amount        = $price['purchase_discount_amount'] ?? null;
        $stock->current_price                   = $price['current_price'] ?? null;
        $stock->purchase_price                  = $price['purchase_price'] ?? null;

        return (array) $stock;
    }

    public function importStocksGforce($apiUrl,$location,$importedTab,$supplier)
    {
        $client = new Client();
        $res = $client->request('GET', $apiUrl);

        // Load the XML
        $xmlResponse = simplexml_load_string($res->getBody());

        // JSON encode the XML, and then JSON decode to an array.
        $responseArray = json_decode(json_encode($xmlResponse), true);

        if(isset($responseArray['vehicle'])) {
            foreach ($responseArray['vehicle'] as $key=>$item) {
                try {

                    $data['derivative_id'] = empty($item['capid']) ? null : $item['capid'];
                    $data['make'] = $item['manufacturer'];
                    $data['model'] = $item['model'];
                    $data['stock_ref'] = $item['identifiers']['stockid'];
                    $data['registration_no'] = $item['identifiers']['vrm'];
                    $data['doors'] = empty($item['doorcount']) ? null : $item['doorcount'];
                    $data['colours'] = $item['colours']['exterior']['manufacturer'];
                    $description = !empty($item['description']) ? $item['description'] : "";
                    $equipmentlist = !empty($item['equipmentlist']) ? $item['equipmentlist'] : "";

                    if(trim($description) != "" && trim($equipmentlist) != "") {
                        $data['description'] = $description  . ", " . $equipmentlist ;
                    } else if(trim($equipmentlist) != "") {
                        $data['description'] = $equipmentlist ;
                    } else {
                        $data['description'] = $description ;
                    }

                    $data['model_year'] = $item['year'];
                    $data['car_type'] = 'used';
                    $data['body_type'] = $item['bodystyle'];
                    $data['fuel_type'] = $item['fueltype'];
                    $data['transmission'] = $item['transmission'];
                    $data['vin_number'] = empty($item['identifiers']['vin']) ? null : $item['identifiers']['vin'];
                    $data['current_mileage'] = $item['odometer']['reading'];
                    $data['registration_date'] = $item['registrationdate'];
                    $data['price'] = $item['price']['current'];
                    $data['sale_location'] = $location;
                    $data['supplier'] = $supplier;

                    if (!$this->isValidUsedCarMileage($data['current_mileage']) || !$this->isValidUsedCarAge($data['model_year'])) {
                        LogHelper::save('WARNING', new \Exception('Car mileage >' . config('constants.stocks.max_used_car_mileage') . ' or registration year greater than ' . config('constants.stocks.max_used_car_age') . ' years. Stock ref:' . $data['stock_ref'] . '  CAP_ID:' . $data['derivative_id'] . '  Mileage:' . $data['current_mileage'] . '  RegDate:' . $data['registration_date']));
                        continue;
                    }

                    if ($item['bodystyle'] == 'Motorcycle') {
                        LogHelper::save('WARNING', new \Exception('Car bodystyle Motorcycle. Stock ref:' . $data['stock_ref'] . '  CAP_ID:' . $data['derivative_id']));
                        continue;
                    }

                    $stock = $this->findCderAndFormatStockArrGforce($data);

                    if (!$stock) {
                        LogHelper::save('WARNING', new \Exception('Cder not found driftbridge audi gforce. Stock ref:' . $data['stock_ref'] . '  CAP_ID:' . $data['derivative_id']));
                        continue;
                    }

                    $stock['supplier'] = $supplier;
                    $stock['imported_tab'] = $importedTab;
                    $stock['created_at'] = (string)Carbon::now();
                    $stock['updated_at'] = (string)Carbon::now();

                    $objStock = Stock::incrementOrInsert($stock);
                    $imageUrl = null;
                    if (is_array($item['images']) && !empty($item['images'])) {
                        $imageUrl = [];
                        foreach ($item['images'] as $imageURL) {
                            $imageUrl[] = str_replace('http://', 'https://', $imageURL);
                        }
                    }
                    if ($objStock instanceof Stock) {
                        try {
                            StockRepo::addStockImages($objStock, $imageUrl);
                        } catch (\Exception $e) {
                            LogHelper::save('ERROR', new \Exception($e->getMessage() .
                                " Could issue with storing/resizing images. stock id: " . $objStock->id .
                                json_encode([$objStock->derivative_id], JSON_PRETTY_PRINT)));

                        }
                    }
                }
                catch (\Exception $e) {
                    echo $e->getMessage();
                    LogHelper::save('ERROR', new \Exception($e->getMessage() .
                        " Something went wrong. derivative id: ". $data['derivative_id'] .
                        " stock_ref: " . $data['stock_ref'] . " sale location: " . $location . " supplier: " . $supplier));

                }
            }
        }
    }

    protected function getRegistrationDate($regDate)
    {
        return $regDate ?
            Carbon::createFromFormat('Y-m-d', $regDate) : null;
    }

    public static function importStocks($apiUrl,$importedTab)
    {
        return true;
    }

    public function findCderAndFormatStockArr()
    {
        return true;
    }
}

