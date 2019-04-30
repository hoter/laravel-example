<?php namespace App\Services;

/**
 * RatingService class
 */
class FinancialService
{

    /**
     * Usages:
     * $financials = FinancialCalculator::getFinancials(7700, 200, 60, 4.75, 0, 275, 175, true, true);
     * return result:
     * array:14 [
    "cashPrice" => 7700
    "loanAmount" => 7500
    "term" => 60
    "gfv" => 0
    "flatRate" => 4.75
    "apr" => 11.61
    "adminFee" => 275
    "finalFee" => 175.0
    "totalInterest" => 1846.56
    "totalCostOfCredit" => 2296.56
    "totalRepayable" => 9796.56
    "monthlyPayment" => 160.36
    "monthlyPaymentExceptLastPayment" => 160.36
    "lastMonthlyPayment" => 335.36
    ]
     *
     * @param $cashPrice : Price of the vehicle
     * @param $deposit : Deposit of the loan
     * @param $term : Loan term
     * @param $flatRate: Flat rate
     * @param int $gfv: Future value
     * @param int $adminFee : Any document / admin fees
     * @param int $finalFee : Any option to purchase fee / transfer fee payable with last installment
     * @param bool $applyInterestOnAdminFee : Whether the admin fee is interest free or not. Pass true if you want to
     *        apply interest on admin fee
     * @param bool $calculateApr : Pass true if you want to get calculated APR
     * @return array
     */
    public static function getFinancials($cashPrice, $deposit, $term, $flatRate, $gfv = 0, $adminFee = 0,
                                         $finalFee = 0, $applyInterestOnAdminFee = true){

        $loanAmount = $cashPrice - $deposit;
        $interestOnCapital = $loanAmount*($flatRate/100)*($term/12);
        $interestOnFees = 0;
        if($applyInterestOnAdminFee) {
            $interestOnFees =  $adminFee*($flatRate/100)*($term/12);
        }
        $totalInterest = $interestOnCapital + $interestOnFees;
        $totalFees = $adminFee + $finalFee;
        $totalCharge = $interestOnCapital + $interestOnFees + $totalFees;
        $totalRepayable = $loanAmount + $totalCharge;

        $monthlyPayment = $monthlyAmountExceptLastPayment = ($totalRepayable - $finalFee) / $term;
        $lastMonthlyPayment = $monthlyAmountExceptLastPayment +  $finalFee;

        return array(
            "cashPrice"  => $cashPrice,
            "loanAmount" => $loanAmount,
            "term"      => $term,
            "gfv"       => $gfv,
            "flatRate"  => $flatRate,
            "apr"       => false,
            "adminFee" => $adminFee,
            "finalFee" => round($finalFee, 2),
            "totalInterest" => round($totalInterest, 2),
            "totalCostOfCredit" => round($totalCharge, 2),
            "totalRepayable" => round($totalRepayable, 2),
            "monthlyPayment" => round($monthlyPayment, 2),
            "monthlyPaymentExceptLastPayment" => round($monthlyAmountExceptLastPayment, 2),
            "lastMonthlyPayment" => round($lastMonthlyPayment, 2)
        );

    }

    /**
     * Calculate APR
     */
    public static function RATE($nper, $pmt, $pv, $fv = 0.0, $type = 0, $guess = 0.1) {
        $FINANCIAL_MAX_ITERATIONS = 128;
        $FINANCIAL_PRECISION = 1.0e-08;

        $rate = $guess;
        if (abs($rate) < $FINANCIAL_PRECISION) {
            $y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
        } else {
            $f = exp($nper * log(1 + $rate));
            $y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
        }
        $y0 = $pv + $pmt * $nper + $fv;
        $y1 = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
        $i = $x0 = 0.0;
        $x1 = $rate;
        while ((abs($y0 - $y1) > $FINANCIAL_PRECISION) && ($i < $FINANCIAL_MAX_ITERATIONS)) {
            $rate = ($y1 * $x0 - $y0 * $x1) / ($y1 - $y0);
            $x0 = $x1;
            $x1 = $rate;
            if (abs($rate) < $FINANCIAL_PRECISION) {
                $y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
            } else {
                $f = exp($nper * log(1 + $rate));
                $y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
            }
            $y0 = $y1;
            $y1 = $y;
            ++$i;
        }
        return round($rate*100*12, 2);
    }

    public static function getMonthlyPayment($gfv, $loanAmount, $apr, $loanTerm)
    {
        $loanAmount = str_replace(',', '', $loanAmount);
        $apr = $apr / (12 * 100);
        $pmt = self::PMT($apr, $loanTerm, $loanAmount, $gfv);
        $amount = -1 * $pmt;
        return round($amount, 2);
    }//end of function getMonthlyInstallment

    private static function PMT($apr, $loanTerm, $loanAmount, $gfv = 0.0, $type = 0)
    {
        $pmt = self::calculate_pmt($apr, $loanTerm, $loanAmount, -$gfv, $type);
        return (is_finite($pmt) ? $pmt : null);
    }

    private static function calculate_pmt($apr, $loanTerm, $loanAmount, $gfv, $type)
    {
        // Calculate the PVIF and FVIFA
        $pvif = self::calculate_pvif($apr, $loanTerm);
        $fvifa = self::calculate_fvifa($apr, $loanTerm);
        return ((-$loanAmount * $pvif - $gfv) / ((1.0 + $apr * $type) * $fvifa));
    }

    private static function calculate_pvif($apr, $loanTerm)
    {
        return (pow(1 + $apr, $loanTerm));
    }

    private static function calculate_fvifa($apr, $loanTerm)
    {
        // Removable singularity at rate == 0
        if ($apr == 0)
            return $loanTerm;
        else
            // FIXME: this sucks for very small rates
            return (pow(1 + $apr, $loanTerm) - 1) / $apr;
    }

}