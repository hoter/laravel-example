<?php

namespace App\Console\Commands;

use DB;
use App\Models\StockImage;
use App\Services\Images\CapImageService;
use Illuminate\Console\Command;
use Mockery\Exception;

class UpdateImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:image {--car_type=} {--supplier=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Utility for stock image update';

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
        $options = $this->options();
        $car_type_sql = "";
        if(isset($options["car_type"]) && config("fields.stocks.car_type.{$options["car_type"]}")) {
            $car_type_sql = " AND stocks.car_type = '" . $options["car_type"] . "'";
        }
        $supplier_sql = "";
        if(isset($options["supplier"]) &&  $this->validateSupplier($options["supplier"])) {
            $supplier_sql = " AND stocks.supplier = '" . $options["supplier"] . "'";
        }

        $service = CapImageService::create();
        $stocks = DB::select("
                        SELECT DISTINCT stocks.`derivative_id`
                        FROM stocks
                        LEFT JOIN stock_images ON stocks.`derivative_id` = stock_images.`related_id` 
                        AND stock_images.type = 'derivative'
                        WHERE (
                            stocks.`car_type` = 'new'
                            OR (stocks.`car_type` = 'used' AND stocks.`supplier` = 'BCA')
                        )
                        AND  stock_images.id IS NULL {$car_type_sql} {$supplier_sql}");


        $bar = $this->output->createProgressBar(count($stocks));

        foreach ($stocks as $item){
            $derivativeId = $item->derivative_id;
            /*Get and store thumbnail*/
            try {
                $service
                    ->init($derivativeId, 3, 'thumb')
                    ->load(true);
                $path = $service->getPublicPath();
                DB::update("UPDATE stocks SET thumb_url = '{$path}' WHERE derivative_id = {$derivativeId} and thumb_url IS NULL");

                // load, save and show full size images
                $service->setSize('full');
                $path = $service->setName(3)->load()->getUrl();
                $this->updateStockImage($path, $derivativeId);
                $path = $service->setName(1)->load()->getUrl();
                $this->updateStockImage($path, $derivativeId);
                $path = $service->setName(2)->load()->getUrl();
                $this->updateStockImage($path, $derivativeId);
                $path = $service->setName(4)->load()->getUrl();
                $this->updateStockImage($path, $derivativeId);
                $path = $service->setName(5)->load()->getUrl();
                $this->updateStockImage($path, $derivativeId);
                $path = $service->setName(6)->load()->getUrl();
                $this->updateStockImage($path, $derivativeId);
            } catch(Exception $e) {
                echo "There was issue in reading CAP image. " . $e->getMessage() . "\n";
            }

            $bar->advance();
        }
        $bar->finish();
        $this->info('Images for Stocks Updated Successfully');
    }

    private function validateSupplier($supplier){
        foreach( config("constants.feeds") as $feed) {
             if(isset($feed["supplier"]) && $feed["supplier"] == $supplier) {
                 return true;
             }
        }
        return false;
    }
    private function updateStockImage($path, $related_id, $width = null, $height = null){

        $stoc_image = array('path' => $path,
            'size'       => 'full',
            'type'       => 'derivative',
            'related_id' => $related_id,
            'width'      => $width,
            'height'     => $height
        );
        StockImage::insert( $stoc_image);
    }

}
