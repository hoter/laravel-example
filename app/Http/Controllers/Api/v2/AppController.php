<?php

namespace App\Http\Controllers\Api\v2;

use App\Models\Setting;
use App\Repositories\StockRepo;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends ApiController
{
    /**
     * Get all front end settings
     *
     */
    public function settings()
    {

        function getFields($file)
        {
            return config("fields.$file");
        }

        $data = [
            'validation' => [],
            'settings'   => [
                'aprs'     => app(RatingService::class)->getIndicativeAprs(),
                'ranges'   => app(RatingService::class)->getAgencyLimits(),
                'colours'  => array_keys(config('constants.colours')),
                'stocks'   => [
                    'new'  => StockRepo::getMakesAndModels('new'),
                    'used' => StockRepo::getMakesAndModels('used'),
                ],
                'worldpay' => array_only(config('services.worldpay'), 'client_key'),
            ],
            'fields'     => [
                'stocks'     => getFields('stocks'),
                'filters'    => getFields('filters'),
                'finance'    => getFields('finance'),
                'personal'   => getFields('personal'),
                'address'    => getFields('address'),
                'employment' => getFields('employment'),
                'conversion' => getFields('conversion'),
                'terms'      => getFields('terms'),
                'time'       => getFields('time'),
            ],
            'features' => [
                'finance_only' => Setting::where('name', 'finance_only')->first()->is_active,
            ]
        ];

        return $this->send($data);
    }

    /**
     * Demo route to show session is working again
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function session(Request $request)
    {
        $message = 'Show the current session';

        if ($request->isMethod('post'))
        {
            \Session::put('data', $request->data);
            $message = 'Post data to the session';
        }

        return $this->sendData($message, \Session::all());
    }

    /**
     * Demo route to show data is being submitted OK
     *
     * @return JsonResponse
     */
    public function test()
    {
        return $this->sendInput();
    }

}
