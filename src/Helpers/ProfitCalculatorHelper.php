<?php

namespace App\Helpers;

class ProfitCalculatorHelper {

    private float $revenueRate;
    private int $totalMachines;
    
    public function __construct(float $revenueRate, int $totalMachines = 50) 
    {
        $this->revenueRate = $revenueRate;
        $this->totalMachines = $totalMachines;
    }
    
    public function calculateStepProfit(int $workingMachines, int $repairmenCount): float 
    {
        $income = $workingMachines * $this->revenueRate;
        
        $shortagePenalty = 0;
        if ($workingMachines < 50) {
            $shortagePenalty = $workingMachines * 20;
        }
        
        $repairmenCost = $repairmenCount * 3.75;
        
        $excessPenalty = ($this->totalMachines - 50) * 30;
        
        $profit = $income - $shortagePenalty - $repairmenCost - $excessPenalty;
        
        return round($profit, 2);
    }
    
    public function calculatePeriodProfit(array $stepProfits): float {
        return array_sum($stepProfits);
    }
}