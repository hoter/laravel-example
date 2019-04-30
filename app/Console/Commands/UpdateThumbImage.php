<?php

namespace App\Console\Commands;

use DB;
use App\Services\Images\CapImageService;
use Illuminate\Console\Command;
use Mockery\Exception;
use Storage;

class UpdateThumbImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:thumb-image {--car_type=} {--supplier=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Utility for stock image update';

    protected $thumbImageName = "3";
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

        $this->info('Start: Updating thumb url for missing thumb of CAP car');
        $service = CapImageService::create();
        $stocks = DB::select("
                        SELECT `id`, derivative_id
                        FROM stocks
                        WHERE (
                            `car_type` = 'new'
                            OR (`car_type` = 'used' AND stocks.`supplier` = 'BCA')
                        )
                        AND  thumb_url IS NULL {$car_type_sql} {$supplier_sql}");


        $bar = $this->output->createProgressBar(count($stocks));

        foreach ($stocks as $item){
            $derivativeId = $item->derivative_id;
            /*Get and store thumbnail*/
            try {
                $path = "/cap/" . $derivativeId . "/" .  $this->thumbImageName . "-300x225.jpg";
                $root = trim(config('constants.images.path'), '/') . '/';
                $image_exists = Storage::disk('s3')->exists($root . $path);
                if($image_exists) {
                    $path =config("constants.images.cdn_url")  . $root . $path;
                } else {
                    $service
                        ->init($derivativeId, 3, 'thumb')
                        ->load(true);
                    $path = $service->getPublicPath();
                }
                DB::update("UPDATE stocks SET thumb_url = '{$path}' WHERE id = {$item->id}");

            } catch(Exception $e) {
                echo "There was issue in reading CAP image. " . $e->getMessage() . "\n";
            }

            $bar->advance();
        }
        $bar->finish();
        $this->info('Complete: Updating thumb url for missing thumb of CAP car');
    }

    private function validateSupplier($supplier){
        foreach( config("constants.feeds") as $feed) {
             if(isset($feed["supplier"]) && $feed["supplier"] == $supplier) {
                 return true;
             }
        }
        return false;
    }

}
