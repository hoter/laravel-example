<?php

namespace App\Models\Cap;

use App\Models\Cap\NVDDictionaryCategory;
use Illuminate\Database\Eloquent\Model;

class NVDDictionaryOption extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'NVDDictionaryOption';
    protected $primaryKey = 'DO_OptionCode';

    public function DictionaryCategory()
    {
        return $this->belongsTo(NVDDictionaryCategory::class,'DO_CatCode','DC_CatCode');
    }
}
