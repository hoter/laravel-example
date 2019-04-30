<?php

namespace App\Http\Controllers\Api\v2\Finance;

use App\Exceptions\NotImplementedException;
use App\Http\Controllers\Api\v2\ApiController;
use App\Http\Requests\Finance\CheckDepositRequest;
use App\Http\Requests\Finance\FinanceOnlyEstimateRequest;
use App\Http\Requests\Finance\GetFinanceOnlyRequest;
use App\Http\Requests\Finance\LikelyOffersRequest;
use App\Models\Customer;
use App\Services\Api\OffersService;
use App\Services\Harlib\HarlibApi;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class FinanceController extends ApiController
{

    public function getRating(Request $request)
    {
        return app(RatingService::class)->getRating($request->score, $request->agency);
    }

    public function setRating(Request $request)
    {
        return app(RatingService::class)->setRating(Customer::current(), $request->score, $request->agency);
    }


    public function getLikelyOffers(LikelyOffersRequest $request, OffersService $service) // OffersService $service
    {
        $offers = $service->getLikelyOffers($request);
        return $this->send($offers);
    }

    /**
     * Check deposit / lender options
     *
     * @param CheckDepositRequest $request
     * @param HarlibApi           $api
     *
     * @return JsonResponse
     */
    public function checkDeposit(CheckDepositRequest $request, HarlibApi $api)
    {
        return collect($api->get('finance/application/deposit', $request->all()))
            ->map(function ($value, $key) {
                return ['deposit' => $key, 'lenders' => $value];
            })->values();
    }

    public function product(){
        throw new NotImplementedException('Search by product is not yet implemented');
    }

    public function estimate(FinanceOnlyEstimateRequest $request, HarlibApi $api)
    {
        return $api->post('finance-only/estimate', $request->all());
    }

    public function getFinanceOnlyOffers(GetFinanceOnlyRequest $request, OffersService $service)
    {
        $offers = $service->getFinanceOnlyOffers($request);
        return $this->send($offers);
    }

    public function sendFinanceOffer($appRef, HarlibApi $api) {
        //send PDF to customer
        return $api->get('finance-only/send-email/' . $appRef);
    }

}
