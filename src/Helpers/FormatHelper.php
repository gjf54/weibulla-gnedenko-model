<?php

namespace App\Helpers;

class FormatHelper {
    
    public static function number($num, $decimals = 2): string 
    {
        return number_format((float)$num, $decimals, '.', ' ');
    }
    
    public static function currency($num, $decimals = 0): string 
    {
        return number_format((float)$num, $decimals, '.', ' ') . ' ₽';
    }
    
    public static function percent($num, $decimals = 1): string 
    {
        return number_format((float)$num, $decimals, '.', ' ') . '%';
    }
    
    public static function time($hours, $decimals = 1): string 
    {
        return number_format((float)$hours, $decimals, '.', ' ') . ' ч';
    }
    
    public static function profitColor($value): string 
    {
        return $value >= 0 ? 'profit-positive' : 'profit-negative';
    }
    
    public static function statusBadge($status): string 
    {
        return $status === 'active' ? 'badge-success' : 'badge-secondary';
    }
}