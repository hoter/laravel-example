<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        foreach (glob(app_path() . '/Helpers/*.php') as $filename) {
            require_once($filename);
        }

        Blade::directive('get', function ($path) {
            return $this->getTag('get', $path);
        });

        Blade::directive('post', function ($path) {
            return $this->getTag('post', $path);
        });
    }

    protected function getTag($method, $path)
    {
        $text = preg_replace('/(\?.*)$/', '<em>$1</em>', $path);
        $label = '<span><label>' . $method . '</label></span>';
        $html = '<li data-method="' . $method . '">' . $label . '<a href="/api/v2/' . $path . '">' . $text . '</a></li>';
        return "<?php echo '$html'; ?>";
    }
}
