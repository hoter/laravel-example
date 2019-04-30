<?php
/**
 * Created by PhpStorm.
 * User: rana
 * Date: 7/17/17
 * Time: 12:58 PM
 */

namespace App\Http\Controllers\Admin;


use App\Helpers\XmlToArrayParser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockFeedRequest;
use App\Repositories\Stocks\Driftbridge\UsedCarGforce;
use App\Repositories\Stocks\Driftbridge\UsedCarGforce2;
use App\Services\Images\GforceImageService;
use App\Services\StockService;
use GuzzleHttp\Client;

class StockFeedController extends Controller
{

    public function upload(StockFeedRequest $request)
    {
        $service = app(StockService::class);
        $files = $service->uploadFile($request);

        foreach ($files as $fileName => $file) {

            $className = config('constants.feeds')[$fileName]['class'];
            $className::importStocks($file->getPathname(), $fileName); //$fileName means supplier source file

            $success = 'Stock feed has been successfully imported.';

            return redirect()->back()->with(compact('vehicles', 'success'));
        }
    }

    public function importGforceStockFeed()
    {
        $importedTab = 'gForce';
        $service = app(StockService::class);
        $service->moveToStockHistoryByImportedTab($importedTab);

        $client = new Client();
        $response = $client->request('GET', config("constants.gforce.gforce_driftbridge.api_url"));

        $responseArray  = (new XmlToArrayParser($response->getBody()))->toArray();

        $gForceObj = new UsedCarGforce(null , null);

        foreach ($responseArray['response']['dealers']['dealer'] as $key=>$item)
        {
            $location = trim($item['location']);
            $link = trim($item['_links']['self']);
            $supplier = $item['group'];

            if(in_array($supplier,config("constants.gforce.allowed_groups"))) {

                $gForceObj->importStocksGforce($link,$location,$importedTab,$supplier);
            }
        }

        return redirect('/admin')->with(['success','Stock feed has been successfully imported.']);

    }

}