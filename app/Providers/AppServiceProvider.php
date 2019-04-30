<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\Harlib\HarlibApi;
use App\Services\Logger;
use App\Services\Stocks\PricingService;
use App\Services\Stocks\TaxCalculationService;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Config;
use davestewart\sketchpad\SketchpadServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Worldpay\Worldpay;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('stock_feed_validator', function ($attribute, $value, $parameters, $validator) {
            return in_array(strtolower($value->clientExtension()), ['csv', 'xls', 'xlsx']);
        });

        Validator::replacer('stock_feed_validator', function ($message, $attribute, $rule, $parameters) {
            return "The $attribute field must be a file of type csv, xls and xlsx.";
        });

        // ensure stocks filesystem url works properly.
        // can't do this in the config file as the URL class isn't initialized!
        $key  = 'filesystems.disks.stocks.url';
        $path = config($key);
        Config::set($key, url($path));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // ide helper
        if ($this->app->environment() !== 'production')
        {
            $this->app->register(IdeHelperServiceProvider::class);
            if (class_exists('\davestewart\sketchpad\SketchpadServiceProvider'))
            {
                $this->app->register(SketchpadServiceProvider::class);
            }
        }

        // logger
        $this->app->singleton(Logger::class, function () {
            $path = Setting::getValue('log_file_path');
            return new Logger($path);
        });

        // harlib api
        $this->app->bind(HarlibApi::class, function () {
            $config = config('services.harlib');
            return new HarlibApi($config);
        });

        // Pricing Service binding
        $this->app->singleton(PricingService::class, function () {
            return new PricingService();
        });

        // Tax calculation service binding
        $this->app->singleton(TaxCalculationService::class, function(){
           return new TaxCalculationService();
        });

        // Worldpay service
        $this->app->singleton('worldpay', function(){
            $config   = (object) config('services.worldpay');
            return new Worldpay($config->service_key);
        });

    }
}
