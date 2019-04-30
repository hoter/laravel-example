<?php

namespace App\Models\Cap;

// FIXME this looks to be missing
use App\Models\FiatDerivative;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Collections\CellCollection;

class CAPDer extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'CAPDer';
    protected $primaryKey = 'cder_ID';

    protected  $convertibleWords = [
        'convertible'
    ];

    // -------------------------------------------------------------------------------------------------------------------
    // cap lookups. See config/fields/cap.php

    /**
     * Returns the stocks database enum for a CAP body_type key
     *
     * @param string $key The CAP body_type key, i.e. 'City-Car'
     * @return string       The stocks table body_type enum, i.e. 'city_car'
     */
    public static function getBodyType($key)
    {
        return self::lookup('body_type', $key);
    }

    /**
     * Returns the stocks database enum for a CAP body_type key
     *
     * @param string $key The CAP fuel_type key, i.e. 'P'
     * @return string       The stocks table fuel_type enum, i.e. 'petrol'
     */
    public static function getFuelType($key)
    {
        return self::lookup('fuel_type', $key);
    }

    /**
     * Returns the stocks database enum for a CAP transmission key
     *
     * @param string $key The CAP fuel_type key, i.e. 'Automatic'
     * @return string       The stocks table transmission enum, i.e. 'automatic'
     */
    public static function getTransmission($key)
    {
        return self::lookup('transmission', $key);
    }

    /**
     * Returns the stocks database enum for a CAP body_type key
     *
     * @param string $field
     * @param string $key
     * @return string
     * @throws \Exception
     */
    protected static function lookup($field, $key)
    {
        $value = array_get(config("constants.cap.lookups.$field"), $key);
        if (!$value) {
            throw new \Exception("Could not find CAP key `$key` in config `constants.cap.lookups.$field`");
        }
        return $value;
    }

    // -------------------------------------------------------------------------------------------------------------------
    // excel methods

    public static $bcaTransmissionMap = [
        'Manual Transmission' => 'M',
        'Auto Clutch'         => 'A',
        'CVT'                 => 'C',
        'Auto'                => 'A',
        'Automatic'           => 'A',
    ];
    
    public static function getDoors()
    {
        return self::where('cder_doors', '!=', '0')
            ->groupBy('cder_doors')
            ->orderBy('cder_doors', 'asc')
            ->pluck('cder_doors');
    }

    public static function getDoorsForType($type)
    {
        return DB::connection('sqlsrv')->table('CAPDer')
            ->join('CAPMod', 'cmod_code', '=', 'cder_modcode')
            ->join('NVDBodyStyle', 'cmod_bodystyle', '=', 'bs_code')
            ->select(DB::raw('cder_doors'))
            ->where('bs_description', $type)
            ->whereNull('cder_discontinued')
            ->groupBy('cder_doors')
            ->get()
            ->pluck('cder_doors');

    }
    
    public function validPrice()
    {
        return $this->NVDPrices()->whereNull('PR_EffectiveTo');
    }

    public function NVDPrices()
    {
        return $this->hasMany(NVDPrices::class, 'PR_Id', 'cder_ID')->orderBy('PR_EffectiveFrom','DESC');
    }

    public function validTechData()
    {
        return $this->NVDTechnical()
            ->whereIn('TECH_Techcode', NVDTechnical::$technicalDictionary)
            ->whereNull('TECH_EffectiveTo');
    }

    public function NVDTechnical()
    {
        return $this->hasMany(NVDTechnical::class, 'TECH_Id', 'cder_ID');
    }

    public function range()
    {
        return $this->belongsTo(CAPRange::class, 'cder_rancode', 'cran_code');
    }

    public function make()
    {
        return $this->belongsTo(CAPMan::class, 'cder_mancode', 'cman_code');
    }

    public function capmod()
    {
        return $this->belongsTo(CAPMod::class, 'cder_modcode', 'cmod_code');
    }

    public function price()
    {
        return $this->hasMany(NVDPrices::class, 'PR_Id', 'cder_id');
    }

    /**
     * Get only standard options
     *
     * @property standardOptions
     * @return mixed
     */
    public function standardOptions()
    {
        return $this->belongsToMany(
        NVDDictionaryOption::class,
        'NVDStandardEquipment',
        'SE_id',
        'SE_OptionCode'
        );
    }

    /**
     *  Get only standard options as string
     *
     * @prperty standardOptionsString
     * @return string
     */
    public function getStandardOptionsStringAttribute()
    {
        $this->standardOptions->each(function($item) use (&$options) {
            $options[] = trim($item->DO_LongDescription);
        });

        return join(", ", (array) $options);
    }
    /**
     * Determine if the cder is convertible
     *
     * @return int
     */
    public function isConvertible()
    {
        $pattern = '/'. join('|', $this->convertibleWords) .'/i';
        return (boolean) preg_match($pattern, $this->capmod->cmod_name);
    }
}