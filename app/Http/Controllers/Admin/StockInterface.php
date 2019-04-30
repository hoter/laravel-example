<?php
/**
 * Created by PhpStorm.
 * User: rana
 * Date: 7/20/17
 * Time: 4:33 PM
 */

namespace App\Http\Controllers\Admin;


interface StockInterface
{
    public function importStocks();
    public function formatStocks($stocks);
}