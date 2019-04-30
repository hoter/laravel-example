<?php namespace App\Services\Api;

use App\Services\Harlib\HarlibApi;

/**
 * Service to collate common data for offers to Harlib
 */
class OffersService
{
    protected $api;

    public function __construct(HarlibApi $api)
    {
        $this->api = $api;
    }

    public function getLikelyOffers($request)
    {
        $params = app(HarlibService::class)->getOffersData($request);
        $params['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? null;
        return $this->api
            ->post('finance/likely-offers', $params)
            ->all();
    }

    public function getFinanceOnlyOffers($request)
    {
        $params = app(HarlibService::class)->getFinanceOnlyData($request);
        return $this->api
            ->post('finance-only/offers', $params)
            ->all();
    }

}