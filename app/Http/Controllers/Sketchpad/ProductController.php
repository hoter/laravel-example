<?php

namespace App\Http\Controllers\Sketchpad;

use App\Services\Harlib\HarlibApi;
use App\Services\Products\Loan;
use App\Services\Products\PCP;
use App\Services\Products\Product;

class ProductController
{

    public function compareProducts($amount = 1000, $gfv = 500, $term = 12, $apr = 5)
    {
        $pcp = new PCP($amount, $term, $apr, $gfv);
        $hp  = new Loan($amount, $term, $apr);
        return [
            'pcp' => $pcp->toArray(),
            'hp'  => $hp->toArray(),
        ];
    }


}

