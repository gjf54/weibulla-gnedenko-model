<?php

namespace App\Services;

use App\Entity\MachinesStatus;
use App\Helpers\RandomGeneratorHelper;
use App\Helpers\WeibullCalculatorHelper;
use App\Models\RepairJob;

class StepProcessorService
{
    private float $k_repair;
    private float $theta_repair;
    private int $repairmen;
    
    public function __construct(float $k_repair, float $theta_repair, int $repairmen)
    {
        $this->k_repair = $k_repair;
        $this->theta_repair = $theta_repair;
        $this->repairmen = $repairmen;
    }
    
    public function processStep(
        array $machines,
        array $repairQueue,
        int $stepCounter,
        int $stepHours,
        ?callable $historyCallback = null
    ): array {
        $newMachines = $machines;
        $newQueue = $repairQueue;
        $remainingRepairmen = $this->repairmen;
        $repairedCount = 0;
        $workingCount = 0;
        
        foreach ($newMachines as $num => $machine) {
            if ($machine->status === MachinesStatus::WORKING->value) {
                $machine->remaining -= $stepHours;
                $workingCount++;
                
                if ($machine->remaining <= 0) {
                    $machine->status = MachinesStatus::WAITING->value;
                    $machine->failure_step = $stepCounter;
                    $machine->remaining = 0;
                    
                    $repairTime = WeibullCalculatorHelper::generateRepairTime($this->k_repair, $this->theta_repair);
                    
                    $newQueue[] = new RepairJob($num, $repairTime, RandomGeneratorHelper::getU(), $stepCounter);
                    
                    if ($historyCallback) {
                        $historyCallback($num, $stepCounter, 'failure', "Отказ на шаге $stepCounter");
                    }
                }
            }
        }
        
        usort($newQueue, fn($a, $b) => $a->added_step <=> $b->added_step);
        
        $tempQueue = [];
        foreach ($newQueue as $job) {
            $machineNum = $job->machine_num;
            $machine = $newMachines[$machineNum];
            
            if ($remainingRepairmen > 0 && $job->repair_remaining > 0) {
                $remainingRepairmen--;
                $job->repair_remaining -= $stepHours;
                
                if ($job->repair_remaining <= 0) {
                    $job->repair_remaining = 0;
                    
                    $newLifetime = WeibullCalculatorHelper::generateWorkTime($this->k_repair, $this->theta_repair);
                    
                    $machine->status = MachinesStatus::WORKING->value;
                    $machine->remaining = $newLifetime;
                    $machine->repair_count++;
                    $machine->repair_remaining = 0;
                    
                    $repairedCount++;
                    
                    if ($historyCallback) {
                        $historyCallback($machineNum, $stepCounter, 'repair_complete', "Отремонтирован на шаге $stepCounter");
                    }
                } else {
                    $machine->status = MachinesStatus::REPAIR->value;
                    $machine->repair_remaining = $job->repair_remaining;
                    $machine->repair_time = $job->repair_time;
                    $tempQueue[] = $job;
                }
            } else {
                $machine->status = MachinesStatus::WAITING->value;
                $machine->repair_remaining = $job->repair_remaining;
                $machine->repair_time = $job->repair_time;
                $machine->total_downtime += $stepHours;
                $tempQueue[] = $job;
            }
        }
        
        foreach ($newMachines as $machine) {
            if ($machine->status === 'repair' || $machine->status === MachinesStatus::WAITING->value) {
                $machine->total_downtime += $stepHours;
            }
        }
        
        return [$newMachines, $tempQueue, $repairedCount, $workingCount];
    }
}