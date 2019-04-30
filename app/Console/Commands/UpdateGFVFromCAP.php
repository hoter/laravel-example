<?php

namespace App\Console\Commands;

use App\Models\Cap\DailySTFForecast;
use App\Models\Cap\FutureResidual;
use App\Models\GFV;
use App\Models\Setting;
use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateGFVFromCAP extends Command
{
    /**
     * The name and signature of the console command.
     * Pass is_all as true if the complete stock table needs updating with gfv
     *
     * @var string
     */
    protected $signature = 'update:gfv {is_all?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update GFV table from CAP database';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('GFV cron started at ' . date('H:i:s'));

        /**
         * To run for all derivative of stocks table or only the missing one. i.e. Once a stock file is imported, we do not want to
         */
        $isAll = $this->argument("is_all") ? true : false;

        if($isAll) {
            $derivativeIds = Stock::select('derivative_id')
                ->distinct('derivative_id')
                ->where('car_type', 'new')
                ->get()
                ->pluck('derivative_id');
        } else {
            $derivativeIds = DB::table('stocks')
                ->selectRaw('distinct stocks.derivative_id')
                ->leftJoin('gfv', 'stocks.derivative_id', 'gfv.derivative_id')
                ->whereNull('gfv.derivative_id')
                ->where('car_type', 'new')
                ->get()
                ->pluck('derivative_id');
        }

        if(empty($derivativeIds)) {
            $this->info('No derivative found to process gfv. ' . date('H:i:s'));
            return;
        }
        try {
            $i = 0;
            $total = count($derivativeIds);

            foreach ($derivativeIds as $derivativeId) {
                $i++;

                $this->info(date('H:i:s') . ' Processing gfv for ' . $derivativeId . " counting " . $i . "/" . $total);

                $gfvFromCaps = $this->getGFV($derivativeId);
                if($gfvFromCaps->isEmpty()) {
                    $this->info('Could not find GFV for derivative in CAP database: ' . $derivativeId);
                }

                foreach ($gfvFromCaps as $gfvFromCap) {
                    $gfvData = [
                        "term_12" => $gfvFromCap->fr_12,
                        "term_18" => $gfvFromCap->fr_18,
                        "term_24" => $gfvFromCap->fr_24,
                        "term_30" => $gfvFromCap->fr_30,
                        "term_36" => $gfvFromCap->fr_36,
                        "term_42" => $gfvFromCap->fr_42,
                        "term_48" => $gfvFromCap->fr_48,
                        "term_54" => $gfvFromCap->fr_54,
                        "term_60" => $gfvFromCap->fr_60,
                    ];

                    GFV::updateOrCreate(
                        [
                            'derivative_id' => $derivativeId,
                            'mileage' => $gfvFromCap->fr_mileage
                        ], $gfvData
                    );

                    if($this->isGfvOddMileageRequired($gfvFromCaps, $gfvFromCap->fr_mileage)) {
                        $this->addGfvForOddMileage($derivativeId, $gfvFromCap);
                    }
                }
            }
            $this->info('GFV Updated Successfully at ' . date('H:i:s'));
        } catch (\PDOException $e) {
            $this->error($e->getLine());
            $this->error($e->getMessage());
            $this->info('GFV Failed with error at ' . date('H:i:s'));
        }
        return true;
    }

    private function isGfvOddMileageRequired($allGFVs, $mileage){

        /*Dont need odd mileage over 30k, if its already odd i.e. 5, 15 dont need again*/
        if($mileage % 10 != 0 || $mileage > 30) {
            return false;
        } else if($mileage == $allGFVs[0] ->fr_mileage){ /*First item is 10k*/
            return true;
        }else {
            foreach($allGFVs as $gfv) {
                if($gfv->fr_mileage == $mileage - 5) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Save GFV data for odd mileage. i.e. 5,15,25
     * @param $gfvFromCap
     */
    private function addGfvForOddMileage($derivativeId, $gfvFromCap){
        try {
            $gfvExtraMileage = $this->getGfvOddMileage($gfvFromCap);
            if (!empty($gfvExtraMileage)) {
                GFV::updateOrCreate(
                    [
                        'derivative_id' => $gfvExtraMileage['derivative_id'],
                        'mileage' => $gfvExtraMileage['mileage']],
                    $gfvExtraMileage
                );
            }
        }catch(\Exception $ex) {
            $this->info($ex->getMessage());
            $this->info('Could not find GFV for derivative ' . $derivativeId);
        }
    }

    /**
     * Get GFV data from CAP
     *
     * @param $derivativeId
     * @return \Illuminate\Support\Collection
     */
    public function getGFV($derivativeId)
    {
        $maxPubSeq = DB::connection('sqlsrv')->table('FutureResidual')
            ->select('fr_pubseq', 'fr_ID')
            ->where('fr_ID', $derivativeId)
            ->where('fr_12',"!=", 0)
            ->where('fr_18',"!=", 0)
            ->where('fr_24',"!=", 0)
            ->where('fr_30',"!=", 0)
            ->where('fr_36',"!=", 0)
            ->where('fr_42',"!=", 0)
            ->where('fr_48',"!=", 0)
            ->where('fr_54',"!=", 0)
            ->where('fr_60',"!=", 0)
            ->max('fr_pubseq');

        $sqlGfv = DB::connection('sqlsrv')->table('FutureResidual')
            ->select('fr_pubseq', 'fr_ID', 'fr_mileage', 'fr_12','fr_18', 'fr_24', 'fr_30',
                        'fr_36', 'fr_42', 'fr_48', 'fr_54', 'fr_60')
            ->where('fr_ID', $derivativeId);

        $sqlForYear = clone($sqlGfv);
        $results = $sqlForYear->where('fr_year', 0)->where('fr_pubseq', $maxPubSeq)->get();


        if ($results->isEmpty()) {
            $results = $sqlGfv->where('fr_pubseq', $maxPubSeq)->orderBy('fr_year', 'desc')->get();
        }
        return $results;
    }

    /**
     * Get GFV for Odd mileage
     * @param $gfv
     * @return array
     */
    private function getGfvOddMileage($gfv)
    {
        if (empty($gfv)) {
            return [];
        }
        $mileage = $gfv->fr_mileage - 5;

        $derivativeId = $gfv->fr_ID;
        $m1 = 10 * floor($mileage / 10);
        $m2 = 10 * ceil($mileage / 10);

        $firstResult = $mileage >= 10 ? $this->getGFVFromResidual($derivativeId, $m1) : $this->getGFVforTermMileage($derivativeId, $m1);
        $lastResult = $this->getGFVFromResidual($derivativeId, $m2);

        $extraGfrForDerivative = [
            'derivative_id' => $derivativeId,
            'mileage' => $mileage
        ];

        $maxMonths = 60;
        if($mileage == 15) { /*for 15k mileage, we do not need more than 3 years future value*/
            $maxMonths = 36;
        }
        for ($i = 12; $i <= $maxMonths; $i = $i + 6) {

            $terms = 'fr_' . $i;

            $v1 = $this->Round25($firstResult->$terms);
            $v2 = $this->Round25($lastResult->$terms);

            $m = $mileage;

            $extraGfrForDerivative['term_' . $i] = $v1 ?
                $this->Round250($v1 - (floor(($v1 - $v2) / ($m2 - $m1)) * ($m - $m1))):
                0;
        }

        return $extraGfrForDerivative;
    }

    private function getGFVFromResidual($derivativeId, $mileage)
    {
        $maxPubSeq = DB::connection('sqlsrv')->table('FutureResidual')
            ->select('fr_pubseq', 'fr_ID')
            ->where('fr_ID', $derivativeId)
            ->where('fr_12',"!=", 0)
            ->where('fr_18',"!=", 0)
            ->where('fr_24',"!=", 0)
            ->where('fr_30',"!=", 0)
            ->where('fr_36',"!=", 0)
            ->where('fr_42',"!=", 0)
            ->where('fr_48',"!=", 0)
            ->where('fr_54',"!=", 0)
            ->where('fr_60',"!=", 0)
            ->max('fr_pubseq');

        return FutureResidual::where('fr_mileage', $mileage)
            ->where('fr_ID', $derivativeId)
            ->where('fr_pubseq', $maxPubSeq)
            ->first();

    }

    private function getGFVforTermMileage($derivativeId, $mileage)
    {
        $mileage = $mileage == 0 ? 1 : $mileage;
        $df_valuationConditionId = Setting::getValue('df_valuationConditionId');
        $result = DailySTFForecast::where('df_mileage', $mileage)
            ->select('df_Id', 'df_Mileage', 'df_plus12')
            ->where('df_id', $derivativeId)
            ->where('df_valuationConditionId', $df_valuationConditionId)
            ->orderBy('df_dailySequenceId', 'desc')
            ->take(1)
            ->first();
        $result->fr_ID = $derivativeId;
        $result->fr_mileage = $mileage;
        $result->fr_12 = isset($result->df_plus12) ? $result->df_plus12 : 0;

        return $result;

    }

    private function Round25($number)
    {
        return round($number / 25) * 25;
    }

    private function Round250($number)
    {
        return round($number / 250) * 250;
    }
}
