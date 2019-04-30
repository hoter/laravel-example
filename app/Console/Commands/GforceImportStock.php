<?php

namespace App\Console\Commands;

use App\Helpers\XmlToArrayParser;
use App\Repositories\Stocks\Driftbridge\UsedCarGforce;
use App\Services\StockService;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class GforceImportStock extends Command
{
    /**
     * GforceStockFeed console command with two parameter.
     *
     * With no parameter
     * example : php artisan GforceStockFeed:import
     *
     * With Supplier parameter
     * example : php artisan stock:gforce-used --supplier="Chris Variava Limited"
     *
     * With Supllier and Location Parameter
     * example : php artisan stock:gforce-used --supplier="Chris Variava Limited" --location="Mitsubishi"
     *
     * @var string
     */
    protected $signature = 'stock:gforce-used {--supplier=} {--location=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gforce stock feed import';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('gForce cron started at ' . date('H:i:s'));

        $importedTab = 'gForce';
        $options = $this->options();

        $service = app(StockService::class);

        if(!empty($options["supplier"])) {
            $service->moveStockToHistoryBySupplierLocation(trim($options["supplier"]),trim($options["location"]));
        }else{
            $service->moveToStockHistoryByImportedTab($importedTab);
        }

        $client = new Client();
        $response = $client->request('GET', config("constants.gforce.gforce_driftbridge.api_url"));

        $responseArray  = (new XmlToArrayParser($response->getBody()))->toArray();

        $gForceObj = new UsedCarGforce(null , null);

        foreach ($responseArray['response']['dealers']['dealer'] as $key=>$item)
        {
            $location = trim($item['location']);
            $link = trim($item['_links']['self']);
            $supplier = $item['group'];

            if($supplier == 'Thames Motor Group') {
                $brand = trim($item['franchise']);
                $location = $brand . " - " . $location ;
            }


            if(in_array($supplier,config("constants.gforce.allowed_groups"))) {
                if(!empty($options["supplier"]) && !empty($options["location"]) && $options["supplier"] == $supplier && $options["location"] == $location) {

                    $this->info('Start: Importing '. $supplier ."->".$location);
                    $gForceObj->importStocksGforce($link,$location,$importedTab,$supplier);
                    $this->info('End: Importing '. $supplier ."->".$location);
                }elseif(!empty($options["supplier"]) && $options["supplier"] == $supplier && empty($options["location"])) {

                    $this->info('Start: Importing '. $supplier ."->".$location);
                    $gForceObj->importStocksGforce($link,$location,$importedTab,$supplier);
                    $this->info('End: Importing '. $supplier ."->".$location);
                }elseif (empty($options["supplier"]) && empty($options["location"])) {

                    $this->info('Start: Importing '. $supplier ."->".$location);
                    $gForceObj->importStocksGforce($link,$location,$importedTab,$supplier);
                    $this->info('End: Importing '. $supplier ."->".$location);
                }
            }
        }

    }
}
