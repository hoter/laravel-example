<?php

namespace App\Models\Cap;

use App\Helpers\CommonHelper;
use App\Helpers\LogHelper;
use App\Models\Cap\NVDDictionaryOption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NVDOption extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'NVDOptions';

    public static function getOptions($engineId)
    {
        $options = DB::connection('sqlsrv')->select("SELECT OPT_OptionCode, RTRIM(LTRIM(DO_LongDescription)) AS DO_LongDescription, DO_CatCode, RTRIM(LTRIM(DC_Description)) AS DC_Description, OPT_EffectiveFrom, OPT_EffectiveTo, OPT_ModifiedDate, OPT_Basic, OPT_Vat, OPT_Poa, OPT_Default FROM NVDOptions INNER JOIN NVDDictionaryOption ON DO_OptionCode = OPT_OptionCode INNER JOIN NVDDictionaryCategory ON DC_CatCode = DO_CatCode WHERE (OPT_EffectiveTo > GETDATE () or OPT_EffectiveTo IS NULL) AND OPT_Id = $engineId AND  DC_Description NOT LIKE 'Paint%' ORDER BY DC_CatCode ASC");

        if (!empty($options)) {
            return collect($options)->groupBy("DC_Description");
        }

        return [];
    }

    public static function getColorOptions($engineId, $collection = false)
    {
        $colors = DB::connection('sqlsrv')
            ->select("SELECT 
                        OPT_OptionCode, 
                        RTRIM(LTRIM(DO_LongDescription)) AS DO_LongDescription, 
                        DO_CatCode, 
                        RTRIM(LTRIM(DC_Description)) AS DC_Description, 
                        OPT_EffectiveFrom, 
                        OPT_EffectiveTo, 
                        OPT_ModifiedDate, 
                        OPT_Basic, 
                        OPT_Vat, 
                        OPT_Poa, 
                        OPT_Default 
                    FROM 
                      NVDOptions 
                      INNER JOIN NVDDictionaryOption ON DO_OptionCode = OPT_OptionCode 
                      INNER JOIN NVDDictionaryCategory ON DC_CatCode = DO_CatCode 
                  WHERE 
                    (OPT_EffectiveTo > GETDATE () or OPT_EffectiveTo IS NULL) AND 
                    OPT_Id = $engineId AND  
                    DC_Description LIKE 'Paint%' 
                ORDER BY DC_CatCode ASC");

        return empty($colors)? ($collection?collect([]):[]): ($collection?collect($colors):[]);
    }

    /**
     * @param $engineId
     * @param array|string $name
     * @return array|bool
     */
    public static function getOptionsByName($engineId, $name)
    {
            if (is_array($name)) {
                $names = join("','", $name);
                $options = DB::connection('sqlsrv')->select("SELECT OPT_OptionCode, RTRIM(LTRIM(DO_LongDescription)) AS DO_LongDescription, DO_CatCode, RTRIM(LTRIM(DC_Description)) AS DC_Description, OPT_EffectiveFrom, OPT_EffectiveTo, OPT_ModifiedDate, OPT_Basic, OPT_Vat, OPT_Poa, OPT_Default FROM NVDDictionaryOption INNER JOIN NVDOptions ON OPT_OptionCode = DO_OptionCode  INNER JOIN NVDDictionaryCategory ON DC_CatCode = DO_CatCode WHERE OPT_Id = $engineId AND (OPT_EffectiveTo > GETDATE () or OPT_EffectiveTo IS NULL) AND  DO_LongDescription IN ('$names')");
                return $options ? $options : false;
            } else {
                $option = DB::connection('sqlsrv')->select("SELECT OPT_OptionCode, RTRIM(LTRIM(DO_LongDescription)) AS DO_LongDescription, DO_CatCode, RTRIM(LTRIM(DC_Description)) AS DC_Description, OPT_EffectiveFrom, OPT_EffectiveTo, OPT_ModifiedDate, OPT_Basic, OPT_Vat, OPT_Poa, OPT_Default FROM NVDDictionaryOption INNER JOIN NVDOptions ON OPT_OptionCode = DO_OptionCode  INNER JOIN NVDDictionaryCategory ON DC_CatCode = DO_CatCode WHERE OPT_Id = $engineId AND (OPT_EffectiveTo > GETDATE () or OPT_EffectiveTo IS NULL) AND DO_LongDescription = '$name'");
                return $option ? $option[0] : false;
            }
    }

    public static function getOptionsForEngine($modCode)
    {
            $standardOptions = 1;
            $options = self::getOptionsOrderByOptionId($modCode, $standardOptions);
            return $options ? $options : false;
    }

    private static function getOptionsOrderByOptionId($modCode,$standardOptions)
    {
            $options = DB::connection('sqlsrv')->select("SELECT OPT_Id, OPT_OptionCode, DO_LongDescription, DO_CatCode, DC_Description, OPT_EffectiveFrom, OPT_EffectiveTo, OPT_ModifiedDate, OPT_Basic, OPT_Vat, OPT_Poa, OPT_Default FROM NVDOptions INNER JOIN NVDDictionaryOption ON DO_OptionCode = OPT_OptionCode INNER JOIN NVDDictionaryCategory ON DC_CatCode = DO_CatCode WHERE (OPT_EffectiveTo > GETDATE () or OPT_EffectiveTo IS NULL) AND OPT_Id in ($modCode) AND OPT_Default = $standardOptions ORDER BY OPT_Id ASC ");

            return $options ? $options : collect([]);
    }

    public function DictionaryOption()
    {
        return $this->belongsTo(NVDDictionaryOption::class, 'OPT_OptionCode', 'DO_OptionCode');
    }


}
