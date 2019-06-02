<?php
namespace App\Statistics;
use \Exception;
/**
 * Statistical averages
 */
class Operations
{
    /**
     * Calculate the mean average of a list of numbers
     *
     *     ∑⟮xᵢ⟯
     * x̄ = -----
     *       n
     *
     * @param float[] $numbers
     *
     * @return float
     *
     * @throws Exception if the input array of numbers is empty
     */
    public static function mean(array $numbers): float
    { 
        if (empty($numbers)) {
            throw new Exception('Cannot find the average of an empty list of numbers');
        }
        return array_sum($numbers) / count($numbers);
    }

     /**
     * Calculate the max of a list of numbers
     * @param float[] $numbers
     *
     * @return float
     */ 

    public static function max(array $numbers): float 
    {
        if (empty($numbers)) {
            throw new Exception('Cannot find the max of an empty list of numbers');
        }
        return max($numbers);
    }

}
