<?php

namespace App\Helpers;

use App\Helpers\RandomGeneratorHelper;

class WeibullCalculatorHelper {
    
    public static function calculateTheta(float $min, float $max, float $k): float 
    {
        $term_min = pow(-log(1 - U_MIN), 1 / $k);
        $term_max = pow(-log(1 - U_MAX), 1 / $k);
        $theta = ($max - $min) / ($term_max - $term_min);
        return round($theta, 2);
    }
    
    public static function generateTime(float $min, float $theta, float $k, float $u): float 
    {
        if ($u >= 1) $u = 0.999999;
        if ($u <= 0) $u = 0.000001;
        return round($min + $theta * pow(-log(1 - $u), 1 / $k), 2);
    }
    
    public static function generateWorkTime(float $k, float $theta): float 
    {
        $u = RandomGeneratorHelper::getU();
        $time = self::generateTime(MIN_WORK_TIME, $theta, $k, $u);
        return max(MIN_WORK_TIME, min(MAX_WORK_TIME, $time));
    }
    
    public static function generateRepairTime(float $k_repair, float $theta_repair): float 
    {
        $u = RandomGeneratorHelper::getU();
        $time = self::generateTime(MIN_REPAIR_TIME, $theta_repair, $k_repair, $u);
        return max(MIN_REPAIR_TIME, min(MAX_REPAIR_TIME, $time));
    }
}