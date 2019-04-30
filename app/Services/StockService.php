<?php
/**
 * Created by PhpStorm.
 * User: rana
 * Date: 7/18/17
 * Time: 2:35 PM
 */

namespace App\Services;


use App\Models\Stock;
use App\Models\StockHistory;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class StockService
{
    /**
     * save file
     * @param object $request
     * @return array
     */
    public function uploadFile($request)
    {
        $files = $request->allFiles();

        $fileNames = [];
        foreach ($files as $fieldName => $file) {
            $fileNames[$fieldName] = $this->upload($file, $fieldName);
        }
        return $fileNames;
    }


    public function upload($file, $fieldName)
    {
        $fileInfo = pathinfo($file->getClientOriginalName());
        $fileName = $file->path().'/'.$fileInfo['basename'].'/'.$fieldName . '-' . Carbon::now()->toDateString() . '.' . $fileInfo['extension'];
        return $file->move(storage_path('stocks'),$fileName);
//        $file->storeAs(storage_path('stocks'), $fileName);

    }

    public function getStock($filePath)
    {
        return Excel::load($filePath)->get();
    }

    public function saveStock($stocks)
    {
        Stock::insert($stocks);
    }

    public function moveStockToHistory($supplier)
    {
        $previousStocks = Stock::where('supplier', $supplier)->get();
        if (!$previousStocks->isEmpty()) {
            StockHistory::insert($previousStocks->toArray());
            Stock::where('supplier', $supplier)->delete();
        }
    }

    public function removeStockOfSupplier($supplier)
    {
        return Stock::where('supplier', $supplier)->delete();
    }

    /**
     * Move all stocks to stocks history
     *
     * @param $importedTab
     */
    public function moveToStockHistoryByImportedTab($importedTab)
    {
        $previousStocks = Stock::where('imported_tab', $importedTab)->get();
        if (!$previousStocks->isEmpty()) {
            foreach($previousStocks->chunk(30) as $stocks)
                StockHistory::insert($stocks->toArray());
            Stock::where('imported_tab', $importedTab)->delete();
        }
    }

    public function moveStockToHistoryBySupplierLocation($supplier, $location = null)
    {
        $query = Stock::where('supplier', $supplier);
        if(!empty($location)) {
            $query->where('sale_location' , $location);
        }
        $previousStocks = $query->get();

        if (!$previousStocks->isEmpty()) {
            StockHistory::insert($previousStocks->toArray());
            $query->delete();
        }
    }

}