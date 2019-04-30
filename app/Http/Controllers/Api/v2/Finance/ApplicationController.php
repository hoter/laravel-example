<?php

namespace App\Http\Controllers\Api\v2\Finance;

use App\Http\Controllers\Api\v2\ApiController;
use App\Http\Requests\Application\CreateApplicationRequest;
use App\Http\Requests\Application\LenderSpecificOfferRequest;
use App\Services\Api\HarlibService;
use App\Services\Harlib\HarlibApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends ApiController
{
    /**
     * Submit credit info to get better rate (should probably move this to account/profiles/credit)
     *
     * @param CreateApplicationRequest $request
     * @param HarlibService            $service
     * @param HarlibApi                $api
     *
     * @return JsonResponse
     */
    public function create(CreateApplicationRequest $request, HarlibService $service, HarlibApi $api)
    {
        $params = $service->getApplicationData($request);
        $params['car']->colour = $params['car']->colour_spec;
        $params['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? null;
        return $api->post('finance/application/create', $params)->all();
    }

    /**
     * Get lowest / average rate on all cars, based on your profile
     *
     * If a carId is passed, get likely offers on single car, based on your profile
     *
     * @param Request       $request
     * @param HarlibService $service
     * @param HarlibApi     $api
     *
     * @param               $applicationId
     * @return JsonResponse
     */
    public function getOffers(Request $request, HarlibService $service, HarlibApi $api, $applicationId)
    {
        if($applicationId == "null") {
            $applicationId = "447063";
        }
        $responses['offers'] = $api->get("finance/application/$applicationId/offers")->all();
        $headers = $api->getHeaders();
        $responses['is_complete'] =  intval($headers['is_complete'][0] ?? null);
        $responses['call_back_time'] =  intval($headers['call_back_time'][0] ?? null);
        return response($responses, 200);
    }

    /**
     * Get a single hard-search lender offer
     *
     * @param LenderSpecificOfferRequest $request
     * @param HarlibApi                  $api
     * @param int                        $applicationId
     *
     * @param                            $lenderId
     * @return JsonResponse
     */
    public function fetchOffer(LenderSpecificOfferRequest $request, HarlibApi $api, $applicationId, $lenderId)
    {
        $params = $request->all();
        return $api->post("finance/application/$applicationId/offers/$lenderId/fetch", $params)->all();
    }

    /**
     * Choose a single lender's offer
     *
     * @param \App\Http\Requests\Application\ConfirmLenderRequest $request
     * @param HarlibApi                                           $api
     * @param int                                                 $applicationId
     *
     * @return JsonResponse
     */
    public function chooseOffer(\App\Http\Requests\Application\ConfirmLenderRequest $request, HarlibApi $api, $applicationId, $lenderId)
    {
        return $api->post("finance/application/$applicationId/offers/$lenderId/choose")->all();
    }


}
