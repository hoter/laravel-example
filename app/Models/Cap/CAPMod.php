<?php

namespace App\Models\Cap;

use App\Helpers\CommonHelper;
use Illuminate\Database\Eloquent\Model;

class CAPMod extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'CAPMod';
    protected $primaryKey = 'cmod_code';

    public function bodyStyle(){
        return $this->belongsTo(NVDBodyStyle::class,'cmod_bodystyle','bs_code');
    }

    public static function getModels($manCode = false, $onlyContinued = true)
    {
            if($manCode != false) :
                self::where("cran_mantextcode", $manCode);
            endif;

            if($onlyContinued):
                self::where("(year(cmod_discontinued) >=" . date('Y') . " or cmod_discontinued =0)");
            endif;

            $models = self::select('cran_name', 'cran_code')
                ->distinct()
                ->leftjoin('CAPRange', 'cran_code', '=', 'cmod_rancode')
                ->orderBy('cran_name')
                ->get();

            return $models ? $models : collect([]);
    }
}
